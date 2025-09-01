# Deployment Guide

This guide explains how to properly deploy the Laravel application and prevent cache-related issues.

## The Problem

The error you encountered (`Route [admin.security.settings] not defined`) happens when:

1. **Route cache is stale**: The cached routes don't include newly added routes
2. **Config cache is outdated**: Cached configuration doesn't reflect environment changes
3. **View cache conflicts**: Compiled views reference routes that aren't in the cached route list

## Prevention Strategies

### 1. Use the Deployment Command

We've created a custom Artisan command that handles proper deployment:

```bash
# Full deployment with asset building
php artisan app:deploy

# Skip asset building (faster for code-only changes)
php artisan app:deploy --skip-assets
```

### 2. Manual Deployment Steps

If you prefer manual deployment, always follow this order:

```bash
# 1. Clear ALL caches first (important!)
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 2. Run migrations
php artisan migrate --force

# 3. Install dependencies
composer install --no-dev --optimize-autoloader

# 4. Build assets
npm ci && npm run build

# 5. Cache for production (after clearing)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart workers
php artisan queue:restart
```

### 3. Laravel Forge Integration

For Laravel Forge, use the provided `forge-deployment-script.sh`:

1. Copy the contents of `forge-deployment-script.sh`
2. Paste it into your Forge site's deployment script
3. Enable "Quick Deploy" for automatic deployments

### 4. GitHub Actions (Optional)

For automated deployments, you can use this GitHub Actions workflow:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to server
        uses: appleboy/ssh-action@v0.1.5
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.KEY }}
          script: |
            cd /home/forge/uploads.morton.dev
            ./forge-deployment-script.sh
```

## Cache Management Best Practices

### Development Environment

```bash
# Clear caches during development
php artisan optimize:clear

# This is equivalent to:
# php artisan config:clear
# php artisan route:clear
# php artisan view:clear
# php artisan cache:clear
```

### Production Environment

```bash
# Always clear before caching
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache
```

## Troubleshooting

### Route Not Found Errors

If you get "Route not defined" errors:

```bash
php artisan route:clear
php artisan route:cache
php artisan route:list --name=your-route-name
```

### Config Issues

If configuration seems wrong:

```bash
php artisan config:clear
php artisan config:cache
```

### View Compilation Errors

If views show old content or errors:

```bash
php artisan view:clear
php artisan view:cache
```

### Complete Reset

If all else fails, clear everything:

```bash
php artisan optimize:clear
php artisan optimize
```

## Environment-Specific Notes

### Local Development (DDEV)

```bash
ddev artisan optimize:clear
ddev artisan optimize
```

### Production (Forge)

- Always use the deployment script
- Monitor logs during deployment
- Test critical routes after deployment

### Staging Environment

- Use the same deployment process as production
- Test thoroughly before promoting to production

## Monitoring

### Health Checks

The application includes health check endpoints:

- `/health` - Basic health check
- `/health/detailed` - Detailed system status

### Queue Monitoring

Monitor queue workers after deployment:

```bash
php artisan queue:work --verbose
php artisan queue:failed
```

## Emergency Procedures

### Quick Fix for Route Errors

If the site is down due to route cache issues:

```bash
# Emergency cache clear (fastest)
php artisan route:clear
php artisan config:clear

# Then rebuild
php artisan route:cache
php artisan config:cache
```

### Rollback Procedure

If deployment fails:

1. Enable maintenance mode: `php artisan down`
2. Revert to previous commit: `git reset --hard HEAD~1`
3. Clear caches: `php artisan optimize:clear`
4. Disable maintenance mode: `php artisan up`

## Files Created

This deployment setup includes:

- `app/Console/Commands/DeployCommand.php` - Custom deployment command
- `deploy-script.sh` - General deployment script
- `forge-deployment-script.sh` - Laravel Forge specific script
- `DEPLOYMENT.md` - This documentation

## Next Steps

1. **Update your Forge deployment script** with the provided script
2. **Test the deployment process** on staging first
3. **Set up monitoring** for failed deployments
4. **Document any project-specific deployment steps**

Remember: **Always clear caches before caching** to prevent stale cache issues!