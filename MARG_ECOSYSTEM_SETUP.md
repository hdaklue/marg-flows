# MARG Ecosystem Setup Guide

## Overview

The MARG ecosystem consists of multiple Laravel applications that share authentication, sessions, and infrastructure resources. This guide ensures proper configuration for seamless integration.

## Current Applications

- **flow.marg.test** - MargineerFlows (Main application)
- **links.marg.test** - Links management

## Critical Shared Infrastructure

### 1. Session Sharing

All apps share the same user session, allowing single sign-on (SSO) across the ecosystem.

**Requirements:**
- Same `APP_KEY` (encrypts/decrypts session data)
- Same `SESSION_COOKIE` name
- Same `SESSION_DOMAIN` (with leading `.`)
- Same `SESSION_DRIVER` (redis)
- Same `REDIS_PREFIX` (critical!)

### 2. Shared RBAC Database

All apps connect to the same RBAC database (`marg-rbac`) for unified permissions and roles.

**Configuration:**
```env
RBAC_DB_CONNECTION=rbac
RBAC_DB_DATABASE=marg-rbac
RBAC_DB_HOST=127.0.0.1
```

### 3. Shared Redis Instance

All apps use the same Redis instance for sessions, cache, and queues.

**Configuration:**
```env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
REDIS_PREFIX=marg_database_
```

## Setting Up a New App

### Step 1: Reference Configuration

Copy `/Users/home/Code/.env.shared` to your project as a reference.

### Step 2: Create Your .env

Start with the shared configuration and override app-specific values:

```env
# App-specific (REQUIRED)
APP_NAME=YourAppName
APP_URL=https://yourapp.marg.test
DB_DATABASE=your_app_database

# Copy all shared values from .env.shared
# Including: APP_KEY, SESSION_*, REDIS_PREFIX, RBAC_*, etc.
```

### Step 3: Laravel Herd Configuration

```bash
# Secure the site (if needed)
herd secure yourapp

# Isolate PHP version (if needed)
herd isolate php@8.3
```

### Step 4: Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
```

### Step 5: Test Session Sharing

1. Login to `https://flow.marg.test`
2. Navigate to your app `https://yourapp.marg.test`
3. Should be automatically authenticated

## Critical Configuration Values

### ⚠️ NEVER CHANGE IN INDIVIDUAL APPS

These values **must be identical** across all apps:

```env
APP_KEY=base64:hnAiCHfq9WAEXzFxP0/q/SteYkgSOdZBEmgWdkOhm+Y=
SESSION_DRIVER=redis
SESSION_COOKIE=marg_session
SESSION_DOMAIN=.marg.test
SESSION_ENCRYPT=true
REDIS_PREFIX=marg_database_
CACHE_PREFIX=marg
RBAC_DB_DATABASE=marg-rbac
```

### ✅ CUSTOMIZE PER APP

These values **should be unique** per app:

```env
APP_NAME=UniqueAppName
APP_URL=https://uniqueapp.marg.test
DB_DATABASE=unique_app_database
FILESYSTEM_DISK=local|public|do_spaces
DO_SPACES_BUCKET=app-specific-bucket
```

## Common Issues & Solutions

### Session Not Shared

**Symptoms:**
- User logged into one app but not another
- Session lost when navigating between apps

**Solutions:**
1. Verify `APP_KEY` is identical across all apps
2. Check `REDIS_PREFIX` matches in all `.env` files
3. Confirm `SESSION_DOMAIN=.marg.test` (with leading dot)
4. Run `php artisan config:clear` in all apps
5. Check Redis keys: `redis-cli KEYS "marg_database_*session*"`

### RBAC Permissions Not Working

**Symptoms:**
- User has different permissions in different apps
- Role assignments not reflected

**Solutions:**
1. Verify `RBAC_DB_DATABASE=marg-rbac` in all apps
2. Check database connection: `php artisan tinker --execute="DB::connection('rbac')->table('users')->count();"`
3. Clear RBAC cache: `php artisan cache:forget` with RBAC keys

### Redis Connection Issues

**Symptoms:**
- Session errors
- Cache not working

**Solutions:**
1. Verify Redis is running: `redis-cli ping`
2. Check `REDIS_PREFIX` is set
3. Confirm Redis DB number matches: `REDIS_DB=0`

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    Browser (.marg.test)                      │
│              Cookie: marg_session=abc123                     │
└───────────────┬────────────────┬────────────────────────────┘
                │                │
    ┌───────────▼───────┐   ┌────▼──────────────┐
    │  flow.marg.test   │   │  links.marg.test  │
    │  APP_NAME=Flows   │   │  APP_NAME=Links   │
    └───────────┬───────┘   └────┬──────────────┘
                │                │
                │    Same Keys   │
                └────────┬───────┘
                         │
            ┌────────────▼────────────┐
            │     Redis (DB 0)        │
            │  marg_database_*        │
            │  - Sessions             │
            │  - Cache                │
            │  - Queues               │
            └─────────────────────────┘
                         │
            ┌────────────▼────────────┐
            │   MySQL: marg-rbac      │
            │  - Users                │
            │  - Roles                │
            │  - Permissions          │
            └─────────────────────────┘
```

## Testing Checklist

Before deploying a new app:

- [ ] Login to flow.marg.test
- [ ] Navigate to new app - verify auto-authentication
- [ ] Check user permissions match
- [ ] Verify cache is shared (set value in one app, read in another)
- [ ] Test session persistence across apps
- [ ] Logout from one app - verify logout from all apps

## Maintenance

### Adding a New Shared Service

1. Update `/Users/home/Code/.env.shared`
2. Update this documentation
3. Deploy to all apps in ecosystem
4. Test integration

### Rotating APP_KEY

⚠️ **DANGEROUS** - Will logout all users across all apps

```bash
# Generate new key
php artisan key:generate --show

# Update .env.shared and all app .env files
# Deploy all apps simultaneously
# Clear all sessions: redis-cli FLUSHDB
```

## Support

For issues or questions about the MARG ecosystem setup, contact the infrastructure team.

---

**Last Updated:** 2025-10-06
**Maintained By:** Development Team