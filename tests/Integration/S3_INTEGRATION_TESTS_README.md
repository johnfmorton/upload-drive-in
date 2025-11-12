# S3 Provider Integration Tests

This document explains how to run integration tests for the Amazon S3 storage provider.

## Overview

The S3 integration tests (`S3ProviderIntegrationTest.php`) verify the S3Provider implementation against a real AWS S3 bucket or S3-compatible service. These tests create, manipulate, and delete actual files in S3.

## Prerequisites

### 1. AWS Account and S3 Bucket

You need:
- An AWS account with S3 access
- An S3 bucket for testing (dedicated test bucket recommended)
- AWS IAM credentials with appropriate permissions

### 2. IAM Permissions

The test credentials need the following S3 permissions:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject",
        "s3:ListBucket",
        "s3:GetObjectTagging",
        "s3:PutObjectTagging",
        "s3:GetObjectAcl",
        "s3:PutObjectAcl",
        "s3:CopyObject"
      ],
      "Resource": [
        "arn:aws:s3:::your-test-bucket",
        "arn:aws:s3:::your-test-bucket/*"
      ]
    }
  ]
}
```

## Configuration

### Environment Variables

Add the following to your `.env` file or `.env.testing`:

```env
# Enable integration tests
SKIP_INTEGRATION_TESTS=false

# AWS Credentials
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=my-test-bucket

# Optional: For S3-compatible services (Cloudflare R2, Backblaze B2, etc.)
AWS_ENDPOINT=https://s3.example.com
```

### Using LocalStack (Alternative)

For local testing without AWS costs, you can use LocalStack:

```bash
# Start LocalStack with S3 service
docker run -d \
  --name localstack \
  -p 4566:4566 \
  -e SERVICES=s3 \
  localstack/localstack

# Configure environment for LocalStack
AWS_ACCESS_KEY_ID=test
AWS_SECRET_ACCESS_KEY=test
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=test-bucket
AWS_ENDPOINT=http://localhost:4566
SKIP_INTEGRATION_TESTS=false

# Create test bucket
aws --endpoint-url=http://localhost:4566 s3 mb s3://test-bucket
```

## Running the Tests

### Run All S3 Integration Tests

```bash
php artisan test --filter=S3ProviderIntegrationTest
```

### Run Specific Test

```bash
php artisan test --filter=S3ProviderIntegrationTest::test_full_upload_workflow
```

### Run with Verbose Output

```bash
php artisan test --filter=S3ProviderIntegrationTest --verbose
```

## Test Coverage

The integration tests cover:

### 1. Full Upload Workflow
- File upload with metadata
- Key-based organization (flat storage model)
- System-level credential usage

### 2. File Deletion
- Successful deletion
- Non-existent file handling

### 3. Health Checks
- Connection validation
- Bucket access verification
- Invalid credential handling

### 4. Presigned URLs
- Download URL generation
- Upload URL generation
- Delete URL generation
- URL expiration configuration

### 5. S3-Compatible Services
- Custom endpoint support
- Path-style endpoint addressing
- Cloudflare R2, Backblaze B2 compatibility

### 6. Multipart Upload
- Large file handling (>50MB)
- Chunk-based upload
- Progress tracking
- Part size optimization

### 7. Metadata Operations
- Custom metadata setting
- Metadata retrieval
- Metadata updates

### 8. File Tagging
- Tag addition
- Tag retrieval
- Multiple tag support

### 9. Storage Classes
- Available storage class listing
- Storage class transitions
- Invalid storage class handling

### 10. Provider Capabilities
- Capability reporting
- Feature detection
- Authentication type verification

## Test Data Cleanup

The tests automatically clean up created files after each test. However, if tests fail unexpectedly, you may need to manually clean up:

```bash
# List files in test bucket
aws s3 ls s3://your-test-bucket/integration-tests/

# Remove all test files
aws s3 rm s3://your-test-bucket/integration-tests/ --recursive
```

## Troubleshooting

### Tests Are Skipped

If you see "No tests found" or tests are skipped:

1. Check `SKIP_INTEGRATION_TESTS` is set to `false`
2. Verify all required environment variables are set
3. Ensure credentials are valid

### Authentication Errors

```
InvalidAccessKeyId: The AWS Access Key Id you provided does not exist
```

Solutions:
- Verify `AWS_ACCESS_KEY_ID` format (20 uppercase alphanumeric characters)
- Check credentials are active in AWS IAM
- Ensure credentials have S3 permissions

### Bucket Access Denied

```
AccessDenied: Access Denied
```

Solutions:
- Verify bucket exists and name is correct
- Check IAM policy includes required permissions
- Ensure bucket is in the correct region

### Network Errors

```
RequestTimeout: Your socket connection to the server was not read from or written to within the timeout period
```

Solutions:
- Check internet connectivity
- Verify AWS endpoint is accessible
- For LocalStack, ensure container is running

### Multipart Upload Failures

```
Failed to upload part X
```

Solutions:
- Check available disk space
- Verify network stability
- Reduce chunk size in test configuration

## Cost Considerations

### AWS S3 Costs

Running integration tests against real AWS S3 incurs costs:

- **Storage**: ~$0.023 per GB-month (Standard)
- **Requests**: ~$0.005 per 1,000 PUT requests
- **Data Transfer**: Free for uploads, $0.09/GB for downloads

**Estimated cost per test run**: < $0.01

### Recommendations

1. Use a dedicated test bucket
2. Enable lifecycle policies to auto-delete old test files
3. Consider using LocalStack for development
4. Run integration tests in CI/CD only when necessary

## CI/CD Integration

### GitHub Actions Example

```yaml
name: S3 Integration Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  s3-integration:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          
      - name: Install Dependencies
        run: composer install
        
      - name: Run S3 Integration Tests
        env:
          SKIP_INTEGRATION_TESTS: false
          AWS_ACCESS_KEY_ID: ${{ secrets.AWS_ACCESS_KEY_ID }}
          AWS_SECRET_ACCESS_KEY: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
          AWS_DEFAULT_REGION: us-east-1
          AWS_BUCKET: ${{ secrets.AWS_TEST_BUCKET }}
        run: php artisan test --filter=S3ProviderIntegrationTest
```

### Using LocalStack in CI

```yaml
services:
  localstack:
    image: localstack/localstack
    ports:
      - 4566:4566
    environment:
      - SERVICES=s3
      - DEBUG=1

steps:
  # ... other steps ...
  
  - name: Setup LocalStack S3
    run: |
      aws --endpoint-url=http://localhost:4566 s3 mb s3://test-bucket
      
  - name: Run S3 Integration Tests
    env:
      SKIP_INTEGRATION_TESTS: false
      AWS_ACCESS_KEY_ID: test
      AWS_SECRET_ACCESS_KEY: test
      AWS_DEFAULT_REGION: us-east-1
      AWS_BUCKET: test-bucket
      AWS_ENDPOINT: http://localhost:4566
    run: php artisan test --filter=S3ProviderIntegrationTest
```

## Best Practices

1. **Isolation**: Use a dedicated test bucket separate from production
2. **Cleanup**: Always clean up test files, even on failure
3. **Naming**: Use unique prefixes for test files (e.g., `integration-tests/`)
4. **Credentials**: Never commit credentials to version control
5. **Frequency**: Run integration tests before major releases, not on every commit
6. **Monitoring**: Monitor test bucket for orphaned files
7. **Documentation**: Keep this README updated with any configuration changes

## Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Review test output with `--verbose` flag
- Consult AWS S3 documentation
- Review S3Provider implementation: `app/Services/S3Provider.php`

## Related Documentation

- [S3 Provider Implementation](../../.kiro/specs/amazon-s3-storage-provider-implementation/)
- [Cloud Storage Provider System](../../docs/cloud-storage-provider-system.md)
- [AWS S3 Documentation](https://docs.aws.amazon.com/s3/)
- [LocalStack Documentation](https://docs.localstack.cloud/)
