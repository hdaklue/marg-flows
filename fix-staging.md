# Fix Staging Server 404 Errors

Run these commands on your staging server to deploy the secure file system:

## 1. Clear caches
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## 2. Verify route registration
```bash
php artisan route:list --name=file.serve
```
Should show: `GET files/{path} file.serve`

## 3. Test route generation
```bash
php artisan tinker
```
Then run:
```php
route('file.serve', ['path' => 'test/path'])
```
Should return: `https://flows-stg.margineer.com/files/test/path`

## 4. Manual test
Visit: `https://flows-stg.margineer.com/files/test/path`
- Should redirect to Filament login (not 404)
- If you get 404, the route isn't registered

## 5. If still 404, check deployment
```bash
# Check if FileServeController exists
ls -la app/Http/Controllers/FileServeController.php

# Check if route exists in web.php
grep -n "files/{path}" routes/web.php

# Check git status
git status
git log --oneline -5
```

## 6. Force deployment refresh
If routes still missing:
```bash
composer dump-autoload
php artisan optimize:clear
php artisan optimize
```

The issue is likely cached routes on staging that don't include our new file.serve route.