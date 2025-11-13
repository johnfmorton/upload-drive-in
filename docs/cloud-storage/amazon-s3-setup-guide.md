# Amazon S3 Storage Provider Setup Guide

This guide provides comprehensive instructions for setting up Amazon S3 as a cloud storage provider in the file intake system.

## Table of Contents

- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [AWS Account Setup](#aws-account-setup)
- [S3 Bucket Configuration](#s3-bucket-configuration)
- [IAM User and Permissions](#iam-user-and-permissions)
- [Application Configuration](#application-configuration)
- [Testing the Connection](#testing-the-connection)
- [S3-Compatible Services](#s3-compatible-services)
- [Troubleshooting](#troubleshooting)
- [Security Best Practices](#security-best-practices)
- [Cost Optimization](#cost-optimization)

## Overview

The Amazon S3 storage provider enables your file intake system to store uploaded files in Amazon S3 or S3-compatible services. Unlike Google Drive's per-user OAuth authentication, S3 uses system-level API key authentication, meaning you configure credentials once and all users share the same S3 bucket.

### Key Features

- **System-Level Authentication**: Configure once, use for all users
- **Shared Bucket Architecture**: All files stored in a single S3 bucket
- **Flat Storage Model**: Files organized using key prefixes (client-email/filename)
- **S3-Compatible Services**: Works with Cloudflare R2, Backblaze B2, and others
- **Advanced Features**: Presigned URLs, storage classes, multipart uploads

### Architecture

```
Admin Configuration → System-Level Credentials → Single S3 Bucket
                                                        ↓
                                    All Users Upload to Same Bucket
                                                        ↓
                                    Files Organized by Client Email
```

## Prerequisites

Before you begin, ensure you have:

- [ ] An AWS account (or account with S3-compatible service)
- [ ] Admin access to your file intake system
- [ ] Basic understanding of AWS IAM and S3
- [ ] Access to your application's admin panel

## AWS Account Setup

### Step 1: Create an AWS Account

If you don't have an AWS account:

1. Visit [aws.amazon.com](https://aws.amazon.com)
2. Click "Create an AWS Account"
3. Follow the registration process
4. Verify your email and payment method

### Step 2: Access the AWS Console

1. Sign in to the [AWS Management Console](https://console.aws.amazon.com)
2. Ensure you're in the correct AWS region (e.g., `us-east-1`, `eu-west-1`)
3. Note your selected region - you'll need this later

## S3 Bucket Configuration

### Step 1: Create an S3 Bucket

1. Navigate to the **S3 service** in the AWS Console
2. Click **"Create bucket"**
3. Configure the bucket:

   **Bucket Name**:
   - Must be globally unique
   - Use lowercase letters, numbers, and hyphens only
   - Example: `my-company-file-intake`
   - Cannot be changed after creation

   **Region**:
   - Choose a region close to your users
   - Note the region identifier (e.g., `us-east-1`)

   **Block Public Access**:
   - ✅ Keep all "Block Public Access" settings enabled
   - Your application will access the bucket using API credentials
   - Public access is not required and should be blocked for security

   **Bucket Versioning** (Optional):
   - Enable if you want to keep file history
   - Increases storage costs but provides recovery options

   **Encryption** (Recommended):
   - Enable "Server-side encryption"
   - Choose "Amazon S3 managed keys (SSE-S3)" for simplicity
   - Or use "AWS Key Management Service (SSE-KMS)" for advanced control

4. Click **"Create bucket"**

### Step 2: Configure Bucket Settings (Optional)

**Lifecycle Rules** (Cost Optimization):
```
1. Navigate to your bucket → Management → Lifecycle rules
2. Create rule to transition old files to cheaper storage classes:
   - After 30 days → S3 Standard-IA
   - After 90 days → S3 Glacier
   - After 365 days → S3 Glacier Deep Archive
```

**CORS Configuration** (If using presigned URLs from browser):
```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
        "AllowedOrigins": ["https://your-domain.com"],
        "ExposeHeaders": ["ETag"],
        "MaxAgeSeconds": 3000
    }
]
```

## IAM User and Permissions

### Step 1: Create an IAM User

1. Navigate to **IAM service** in the AWS Console
2. Click **"Users"** → **"Add users"**
3. Configure the user:

   **User name**: `file-intake-s3-user` (or your preferred name)
   
   **Access type**: 
   - ✅ Select "Access key - Programmatic access"
   - ❌ Do NOT select "Password - AWS Management Console access"

4. Click **"Next: Permissions"**

### Step 2: Attach Permissions Policy

**Option A: Use Inline Policy (Recommended)**

1. Select **"Attach policies directly"**
2. Click **"Create policy"**
3. Switch to the **JSON** tab
4. Paste the following policy (replace `YOUR-BUCKET-NAME`):

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "ListBucketAccess",
      "Effect": "Allow",
      "Action": [
        "s3:ListBucket",
        "s3:GetBucketLocation"
      ],
      "Resource": "arn:aws:s3:::YOUR-BUCKET-NAME"
    },
    {
      "Sid": "ObjectAccess",
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject",
        "s3:GetObjectAcl",
        "s3:PutObjectAcl"
      ],
      "Resource": "arn:aws:s3:::YOUR-BUCKET-NAME/*"
    },
    {
      "Sid": "ObjectTagging",
      "Effect": "Allow",
      "Action": [
        "s3:PutObjectTagging",
        "s3:GetObjectTagging",
        "s3:DeleteObjectTagging"
      ],
      "Resource": "arn:aws:s3:::YOUR-BUCKET-NAME/*"
    }
  ]
}
```

5. Click **"Next: Tags"** (optional)
6. Click **"Next: Review"**
7. Name the policy: `FileIntakeS3Policy`
8. Click **"Create policy"**
9. Return to the user creation and attach the new policy

**Option B: Use AWS Managed Policy (Less Secure)**

⚠️ Not recommended for production - grants too many permissions

1. Search for and attach: `AmazonS3FullAccess`
2. This grants access to ALL S3 buckets in your account

### Step 3: Create Access Keys

1. Complete the user creation process
2. On the success page, you'll see:
   - **Access Key ID**: `AKIAIOSFODNN7EXAMPLE` (20 characters)
   - **Secret Access Key**: `wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY` (40 characters)

3. **⚠️ IMPORTANT**: Download the CSV or copy these credentials immediately
4. **⚠️ SECURITY**: The secret access key is shown only once
5. Store credentials securely (password manager, secrets vault)

### IAM Policy Explanation

The policy grants the following permissions:

**Bucket-Level Permissions**:
- `s3:ListBucket`: List objects in the bucket (for health checks)
- `s3:GetBucketLocation`: Get bucket region information

**Object-Level Permissions**:
- `s3:PutObject`: Upload files to the bucket
- `s3:GetObject`: Download files from the bucket
- `s3:DeleteObject`: Delete files from the bucket
- `s3:GetObjectAcl` / `s3:PutObjectAcl`: Manage object access control
- `s3:PutObjectTagging` / `s3:GetObjectTagging` / `s3:DeleteObjectTagging`: Manage object tags

## Application Configuration

### Step 1: Access Admin Panel

1. Log in to your file intake system as an admin
2. Navigate to **Admin Dashboard** → **Cloud Storage**

### Step 2: Select Amazon S3 Provider

1. In the provider dropdown, select **"Amazon S3"**
2. The S3 configuration form will appear

### Step 3: Enter S3 Configuration

Fill in the following fields:

**AWS Access Key ID**:
```
AKIAIOSFODNN7EXAMPLE
```
- 20 uppercase alphanumeric characters
- From the IAM user creation step

**AWS Secret Access Key**:
```
wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
```
- 40 characters (mixed case, alphanumeric, special characters)
- From the IAM user creation step

**AWS Region**:
```
us-east-1
```
- Select from dropdown or enter manually
- Must match your S3 bucket's region
- Common regions:
  - `us-east-1` - US East (N. Virginia)
  - `us-west-2` - US West (Oregon)
  - `eu-west-1` - EU (Ireland)
  - `eu-central-1` - EU (Frankfurt)
  - `ap-southeast-1` - Asia Pacific (Singapore)

**S3 Bucket Name**:
```
my-company-file-intake
```
- The exact name of your S3 bucket
- Case-sensitive
- Must exist in the specified region

**Custom Endpoint** (Optional):
```
Leave blank for standard AWS S3
```
- Only needed for S3-compatible services
- See [S3-Compatible Services](#s3-compatible-services) section

### Step 4: Save Configuration

1. Click **"Save Configuration"**
2. The system will:
   - Validate credential format
   - Encrypt credentials before storing
   - Perform a health check
   - Display connection status

3. If successful, you'll see: ✅ **"Connected"**
4. If failed, see [Troubleshooting](#troubleshooting) section

### Environment Variables (Alternative Method)

You can also configure S3 via environment variables in `.env`:

```env
# Amazon S3 Configuration
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=my-company-file-intake

# Set S3 as default provider
CLOUD_STORAGE_DEFAULT=amazon-s3

# Optional: For S3-compatible services
# AWS_ENDPOINT=https://s3.example.com
# AWS_USE_PATH_STYLE_ENDPOINT=true
```

After updating `.env`:
```bash
ddev artisan config:clear
ddev artisan cache:clear
```

## Testing the Connection

### Step 1: Use Built-in Health Check

1. In the admin panel, navigate to **Cloud Storage**
2. With S3 configured, click **"Test Connection"**
3. The system will:
   - Attempt to list objects in your bucket
   - Verify credentials and permissions
   - Display detailed status

### Step 2: Test File Upload

1. Navigate to the public upload page
2. Enter a test email address
3. Verify the email and upload a test file
4. Check the admin dashboard for upload status
5. Verify the file appears in your S3 bucket

### Step 3: Verify in AWS Console

1. Go to AWS Console → S3 → Your Bucket
2. You should see files organized by client email:
   ```
   client@example.com/
   ├── test-document.pdf
   └── another-file.jpg
   ```

### Step 4: Check Dashboard Widget

1. Return to the admin dashboard
2. The cloud storage status widget should show:
   - ✅ **Connected**
   - Bucket name and region
   - Last health check time

## S3-Compatible Services

The S3 provider works with any service that implements the S3 API. Here are setup guides for popular alternatives:

### Cloudflare R2

**Benefits**:
- No egress fees (free data transfer out)
- S3-compatible API
- Global edge network
- Competitive pricing

**Setup**:

1. **Create R2 Bucket**:
   - Log in to Cloudflare Dashboard
   - Navigate to R2 → Create bucket
   - Choose a bucket name
   - Select location (automatic or specific region)

2. **Generate API Token**:
   - Go to R2 → Manage R2 API Tokens
   - Click "Create API Token"
   - Select permissions: "Object Read & Write"
   - Copy the Access Key ID and Secret Access Key

3. **Get Account ID**:
   - Find your Account ID in the R2 overview page
   - Format: `1234567890abcdef1234567890abcdef`

4. **Configure in Application**:
   ```
   AWS Access Key ID: [Your R2 Access Key ID]
   AWS Secret Access Key: [Your R2 Secret Access Key]
   AWS Region: auto
   S3 Bucket Name: [Your R2 bucket name]
   Custom Endpoint: https://[ACCOUNT_ID].r2.cloudflarestorage.com
   ```

5. **Example Configuration**:
   ```env
   AWS_ACCESS_KEY_ID=abc123def456
   AWS_SECRET_ACCESS_KEY=xyz789uvw012
   AWS_DEFAULT_REGION=auto
   AWS_BUCKET=my-r2-bucket
   AWS_ENDPOINT=https://1234567890abcdef1234567890abcdef.r2.cloudflarestorage.com
   AWS_USE_PATH_STYLE_ENDPOINT=true
   ```

### Backblaze B2

**Benefits**:
- Very low storage costs
- S3-compatible API
- Free egress to Cloudflare
- Simple pricing

**Setup**:

1. **Create B2 Bucket**:
   - Log in to Backblaze
   - Navigate to B2 Cloud Storage → Buckets
   - Click "Create a Bucket"
   - Choose bucket name and privacy settings (Private)

2. **Create Application Key**:
   - Go to App Keys → Add a New Application Key
   - Name: `file-intake-app`
   - Allow access to: Your specific bucket
   - Permissions: Read and Write
   - Copy the keyID and applicationKey

3. **Get Endpoint URL**:
   - Find your endpoint in bucket details
   - Format: `s3.us-west-004.backblazeb2.com`

4. **Configure in Application**:
   ```
   AWS Access Key ID: [Your B2 keyID]
   AWS Secret Access Key: [Your B2 applicationKey]
   AWS Region: us-west-004
   S3 Bucket Name: [Your B2 bucket name]
   Custom Endpoint: https://s3.us-west-004.backblazeb2.com
   ```

5. **Example Configuration**:
   ```env
   AWS_ACCESS_KEY_ID=004abc123def456
   AWS_SECRET_ACCESS_KEY=K004xyz789uvw012
   AWS_DEFAULT_REGION=us-west-004
   AWS_BUCKET=my-b2-bucket
   AWS_ENDPOINT=https://s3.us-west-004.backblazeb2.com
   AWS_USE_PATH_STYLE_ENDPOINT=true
   ```

### DigitalOcean Spaces

**Benefits**:
- Simple pricing ($5/month for 250GB)
- Built-in CDN
- S3-compatible API
- Easy to use

**Setup**:

1. **Create Space**:
   - Log in to DigitalOcean
   - Navigate to Spaces → Create Space
   - Choose datacenter region
   - Enter Space name
   - Select CDN option (optional)

2. **Generate API Keys**:
   - Go to API → Spaces Keys
   - Click "Generate New Key"
   - Name: `file-intake`
   - Copy the Access Key and Secret Key

3. **Configure in Application**:
   ```
   AWS Access Key ID: [Your Spaces Access Key]
   AWS Secret Access Key: [Your Spaces Secret Key]
   AWS Region: [Your region, e.g., nyc3]
   S3 Bucket Name: [Your Space name]
   Custom Endpoint: https://[region].digitaloceanspaces.com
   ```

4. **Example Configuration**:
   ```env
   AWS_ACCESS_KEY_ID=DO00ABC123DEF456
   AWS_SECRET_ACCESS_KEY=xyz789uvw012pqr345stu678
   AWS_DEFAULT_REGION=nyc3
   AWS_BUCKET=my-space-name
   AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
   AWS_USE_PATH_STYLE_ENDPOINT=false
   ```

### MinIO (Self-Hosted)

**Benefits**:
- Self-hosted solution
- Full control over data
- S3-compatible API
- No cloud costs

**Setup**:

1. **Install MinIO**:
   ```bash
   # Docker example
   docker run -p 9000:9000 -p 9001:9001 \
     -e "MINIO_ROOT_USER=admin" \
     -e "MINIO_ROOT_PASSWORD=password123" \
     minio/minio server /data --console-address ":9001"
   ```

2. **Create Bucket**:
   - Access MinIO Console at `http://localhost:9001`
   - Log in with root credentials
   - Create a new bucket

3. **Create Access Key**:
   - Go to Identity → Service Accounts
   - Create new service account
   - Copy Access Key and Secret Key

4. **Configure in Application**:
   ```
   AWS Access Key ID: [MinIO Access Key]
   AWS Secret Access Key: [MinIO Secret Key]
   AWS Region: us-east-1
   S3 Bucket Name: [Your bucket name]
   Custom Endpoint: http://localhost:9000
   ```

## Troubleshooting

### Connection Failed: Invalid Credentials

**Symptoms**:
- ❌ "Invalid AWS credentials" error
- ❌ "SignatureDoesNotMatch" error

**Solutions**:

1. **Verify Access Key ID**:
   - Must be exactly 20 characters
   - All uppercase alphanumeric
   - No spaces or special characters
   - Check for copy/paste errors

2. **Verify Secret Access Key**:
   - Must be exactly 40 characters
   - Case-sensitive
   - Check for trailing spaces
   - Regenerate if lost (cannot be retrieved)

3. **Check IAM User Status**:
   - Ensure user is active (not deleted)
   - Verify access keys are active
   - Check if keys have been rotated

4. **Test with AWS CLI**:
   ```bash
   aws s3 ls s3://your-bucket-name \
     --profile test \
     --region us-east-1
   ```

### Connection Failed: Bucket Not Found

**Symptoms**:
- ❌ "The specified bucket does not exist" error
- ❌ "NoSuchBucket" error

**Solutions**:

1. **Verify Bucket Name**:
   - Check spelling (case-sensitive)
   - Ensure no extra spaces
   - Confirm bucket exists in AWS Console

2. **Check Region**:
   - Bucket must exist in the specified region
   - Use `aws s3api get-bucket-location --bucket your-bucket-name`
   - Update region in configuration

3. **Verify Bucket Ownership**:
   - Ensure bucket belongs to your AWS account
   - Check if bucket was deleted and recreated

### Connection Failed: Access Denied

**Symptoms**:
- ❌ "Access Denied" error
- ❌ "Insufficient permissions" error

**Solutions**:

1. **Review IAM Policy**:
   - Ensure policy is attached to user
   - Verify bucket ARN is correct
   - Check for typos in policy JSON

2. **Check Bucket Policy**:
   - Ensure bucket policy doesn't deny access
   - Review bucket-level permissions

3. **Verify Bucket Encryption**:
   - If using KMS encryption, ensure IAM user has KMS permissions:
   ```json
   {
     "Effect": "Allow",
     "Action": [
       "kms:Decrypt",
       "kms:GenerateDataKey"
     ],
     "Resource": "arn:aws:kms:region:account:key/key-id"
   }
   ```

4. **Test Specific Operations**:
   ```bash
   # Test list
   aws s3 ls s3://your-bucket-name/
   
   # Test upload
   echo "test" > test.txt
   aws s3 cp test.txt s3://your-bucket-name/test.txt
   
   # Test download
   aws s3 cp s3://your-bucket-name/test.txt downloaded.txt
   
   # Test delete
   aws s3 rm s3://your-bucket-name/test.txt
   ```

### Upload Fails: File Too Large

**Symptoms**:
- ❌ Upload times out
- ❌ "EntityTooLarge" error

**Solutions**:

1. **Check File Size Limits**:
   - S3 supports files up to 5TB
   - Single PUT operation: max 5GB
   - Use multipart upload for files > 100MB

2. **Verify Multipart Upload**:
   - System automatically uses multipart for files > 50MB
   - Check logs for multipart upload errors

3. **Increase Timeouts**:
   ```env
   # In .env
   AWS_UPLOAD_TIMEOUT=300
   ```

### S3-Compatible Service Issues

**Symptoms**:
- ❌ Works with AWS but not with alternative service
- ❌ "InvalidRequest" errors

**Solutions**:

1. **Enable Path-Style Endpoints**:
   - Required for some S3-compatible services
   - Automatically enabled when custom endpoint is set
   - Format: `https://endpoint.com/bucket/key` instead of `https://bucket.endpoint.com/key`

2. **Check Endpoint URL**:
   - Must include protocol (`https://`)
   - No trailing slash
   - Verify endpoint is correct for your service

3. **Verify API Compatibility**:
   - Not all S3-compatible services support all S3 features
   - Check service documentation for supported operations

4. **Test with Service-Specific Tools**:
   ```bash
   # Cloudflare R2
   aws s3 ls s3://bucket-name \
     --endpoint-url https://account-id.r2.cloudflarestorage.com
   
   # Backblaze B2
   aws s3 ls s3://bucket-name \
     --endpoint-url https://s3.us-west-004.backblazeb2.com
   ```

### Health Check Fails Intermittently

**Symptoms**:
- ✅ Sometimes connected, ❌ sometimes not
- Inconsistent status in dashboard

**Solutions**:

1. **Check Network Connectivity**:
   - Verify internet connection
   - Check firewall rules
   - Test DNS resolution

2. **Review Rate Limits**:
   - AWS S3: 3,500 PUT/COPY/POST/DELETE, 5,500 GET/HEAD per second per prefix
   - Reduce health check frequency if hitting limits

3. **Check AWS Service Status**:
   - Visit [AWS Service Health Dashboard](https://status.aws.amazon.com/)
   - Check for regional outages

4. **Enable Debug Logging**:
   ```env
   # In .env
   LOG_LEVEL=debug
   ```
   
   Then check logs:
   ```bash
   ddev artisan pail
   ```

### Files Not Appearing in S3

**Symptoms**:
- Upload succeeds in application
- Files not visible in S3 bucket

**Solutions**:

1. **Check Queue Processing**:
   ```bash
   # Ensure queue worker is running
   ddev artisan queue:work
   
   # Check failed jobs
   ddev artisan queue:failed
   ```

2. **Verify File Path**:
   - Files organized by client email
   - Check: `client@example.com/filename.pdf`

3. **Check S3 Console**:
   - Refresh bucket view
   - Check all folders/prefixes
   - Verify region is correct

4. **Review Application Logs**:
   ```bash
   ddev artisan pail
   # Look for upload job errors
   ```

## Security Best Practices

### Credential Management

1. **Never Commit Credentials**:
   - ❌ Don't commit `.env` file
   - ❌ Don't hardcode credentials in code
   - ✅ Use environment variables
   - ✅ Use secrets management (AWS Secrets Manager, HashiCorp Vault)

2. **Rotate Access Keys Regularly**:
   - Create new access key
   - Update application configuration
   - Test thoroughly
   - Delete old access key
   - Recommended: Every 90 days

3. **Use Least Privilege**:
   - Grant only necessary permissions
   - Limit to specific bucket
   - Avoid using root account credentials
   - Use IAM user with minimal policy

4. **Monitor Access**:
   - Enable CloudTrail for API logging
   - Set up CloudWatch alarms for unusual activity
   - Review access logs regularly

### Bucket Security

1. **Block Public Access**:
   - ✅ Keep all "Block Public Access" settings enabled
   - Never make bucket publicly readable
   - Use presigned URLs for temporary access

2. **Enable Encryption**:
   - ✅ Use server-side encryption (SSE-S3 or SSE-KMS)
   - Encrypt data in transit (HTTPS)
   - Consider client-side encryption for sensitive data

3. **Enable Versioning**:
   - Protects against accidental deletion
   - Allows recovery of previous versions
   - Increases storage costs

4. **Configure Bucket Policy**:
   ```json
   {
     "Version": "2012-10-17",
     "Statement": [
       {
         "Sid": "DenyInsecureTransport",
         "Effect": "Deny",
         "Principal": "*",
         "Action": "s3:*",
         "Resource": [
           "arn:aws:s3:::your-bucket-name",
           "arn:aws:s3:::your-bucket-name/*"
         ],
         "Condition": {
           "Bool": {
             "aws:SecureTransport": "false"
           }
         }
       }
     ]
   }
   ```

### Application Security

1. **Encrypt Stored Credentials**:
   - Application automatically encrypts credentials
   - Uses Laravel's encryption
   - Ensure `APP_KEY` is set and secure

2. **Restrict Admin Access**:
   - Only admins can configure S3
   - Use strong passwords
   - Enable 2FA for admin accounts

3. **Audit Logging**:
   - All S3 operations are logged
   - Review logs for suspicious activity
   - Set up alerts for failed operations

4. **Network Security**:
   - Use HTTPS for all connections
   - Configure firewall rules
   - Consider VPC endpoints for AWS (if applicable)

## Cost Optimization

### Storage Classes

Choose the right storage class based on access patterns:

| Storage Class | Use Case | Cost | Retrieval Time |
|--------------|----------|------|----------------|
| **S3 Standard** | Frequently accessed | $$$ | Immediate |
| **S3 Standard-IA** | Infrequent access (>30 days) | $$ | Immediate |
| **S3 Glacier Instant** | Archive with instant retrieval | $ | Immediate |
| **S3 Glacier Flexible** | Archive (minutes to hours) | $ | 1-5 minutes |
| **S3 Glacier Deep Archive** | Long-term archive (>90 days) | ¢ | 12 hours |

### Lifecycle Policies

Automatically transition files to cheaper storage:

```json
{
  "Rules": [
    {
      "Id": "TransitionOldFiles",
      "Status": "Enabled",
      "Transitions": [
        {
          "Days": 30,
          "StorageClass": "STANDARD_IA"
        },
        {
          "Days": 90,
          "StorageClass": "GLACIER"
        },
        {
          "Days": 365,
          "StorageClass": "DEEP_ARCHIVE"
        }
      ]
    }
  ]
}
```

### Cost Monitoring

1. **Enable Cost Allocation Tags**:
   - Tag objects by client, project, or department
   - Track costs per category

2. **Set Up Billing Alerts**:
   - AWS Budgets: Set spending limits
   - CloudWatch: Alert on unusual usage

3. **Review Storage Analytics**:
   - S3 Storage Lens: Analyze usage patterns
   - Identify optimization opportunities

4. **Optimize Data Transfer**:
   - Use CloudFront for frequent downloads
   - Consider S3 Transfer Acceleration for uploads
   - Use S3-compatible services with free egress (Cloudflare R2)

### Cost Comparison

**AWS S3** (us-east-1):
- Storage: $0.023/GB/month (Standard)
- PUT requests: $0.005 per 1,000
- GET requests: $0.0004 per 1,000
- Data transfer out: $0.09/GB (first 10TB)

**Cloudflare R2**:
- Storage: $0.015/GB/month
- Operations: $4.50 per million Class A, $0.36 per million Class B
- Data transfer out: **FREE**

**Backblaze B2**:
- Storage: $0.005/GB/month
- Downloads: $0.01/GB (first 1GB free per day)
- API calls: Free

## Additional Resources

### AWS Documentation
- [S3 User Guide](https://docs.aws.amazon.com/s3/)
- [IAM User Guide](https://docs.aws.amazon.com/iam/)
- [S3 API Reference](https://docs.aws.amazon.com/AmazonS3/latest/API/)

### S3-Compatible Services
- [Cloudflare R2 Documentation](https://developers.cloudflare.com/r2/)
- [Backblaze B2 Documentation](https://www.backblaze.com/b2/docs/)
- [DigitalOcean Spaces Documentation](https://docs.digitalocean.com/products/spaces/)
- [MinIO Documentation](https://min.io/docs/)

### Application Documentation
- [Cloud Storage Provider System](../cloud-storage-provider-system.md)
- [Implementing New Cloud Storage Providers](../implementing-new-cloud-storage-providers.md)
- [Cloud Storage Configuration Guide](../cloud-storage-configuration-guide.md)

## Support

If you encounter issues not covered in this guide:

1. Check application logs: `ddev artisan pail`
2. Review failed queue jobs: `ddev artisan queue:failed`
3. Test AWS CLI connectivity
4. Consult AWS Support or service provider documentation
5. Contact your system administrator

---

**Last Updated**: November 2025  
**Version**: 1.0  
**Applies To**: File Intake System v2.0+
