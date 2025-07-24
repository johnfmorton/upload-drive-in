# User Management Commands - Quick Reference

This document provides a quick reference for all user management commands available in the Upload Drive-In application.

## Available Commands

### Core User Management

| Command | Purpose | Key Options |
|---------|---------|-------------|
| `user:create` | Create new users (default: client) | `--role`, `--password`, `--owner` |
| `user:list` | List users with filtering | `--role`, `--owner` |
| `user:show` | Show detailed user info | None |
| `user:delete` | Delete users | `--force` |
| `user:set-role` | Change user roles | `--role` |

### Authentication & Access

| Command | Purpose | Key Options |
|---------|---------|-------------|
| `user:reset-password` | Reset passwords | `--password` |
| `user:login-url` | Generate login URLs | None |
| `user:toggle-notifications` | Toggle notifications | `--enable`, `--disable` |

## Quick Examples

### Creating Users

```bash
# Create client user (default role, token-based login)
ddev artisan user:create "Client Name" client@example.com

# Create admin user
ddev artisan user:create "Admin User" admin@example.com --role=admin

# Create employee with owner
ddev artisan user:create "Employee Name" employee@example.com --role=employee --owner=admin@example.com
```

**Note**: Name and email are positional arguments, not options. Use quotes around names with spaces.

### Managing Users

```bash
# List all users
ddev artisan user:list

# Show user details
ddev artisan user:show user@example.com

# Change user role
ddev artisan user:set-role user@example.com --role=employee

# Reset password
ddev artisan user:reset-password user@example.com
```

### Client Management

```bash
# Generate login URL for client
ddev artisan user:login-url client@example.com

# Enable notifications for user
ddev artisan user:toggle-notifications user@example.com --enable
```

## User Roles

- **Admin**: Full system access, can own employees
- **Employee**: Limited admin access, belongs to an admin
- **Client**: Basic access, uses token-based login

## Security Notes

- Client users cannot login with passwords (token-based only)
- Admin and employee users can login with passwords
- Generated passwords are shown once during creation
- Login URLs are valid for 7 days
- Use `--force` carefully when deleting users with employees

## Integration with Existing Commands

These new commands work alongside existing user management:

- `admin:2fa-remove` - Remove 2FA for locked-out users
- `users` / `users:list` - Alternative user listing command

All commands follow Laravel conventions and integrate with the existing authentication system.