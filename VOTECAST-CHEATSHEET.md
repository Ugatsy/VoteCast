Here's your complete VoteCast Docker Cheatsheet in Markdown format. Save this as VOTECAST-CHEATSHEET.md in your project folder or on your desktop.

markdown
# 🐳 VoteCast Docker Cheatsheet

> **Important:** Always use Docker commands! Your local PHP (8.2) won't work with this project (requires PHP 8.4+).

---

## 📁 First Step - Navigate to Project

```powershell
cd C:\Users\Hercules\Desktop\VoteCast
🚀 Daily Commands
Action	Command
Start the app	docker-compose up -d
Stop the app	docker-compose down
Restart the app	docker-compose restart
Check status	docker-compose ps
View live logs	docker-compose logs -f app
View last 50 logs	docker-compose logs app --tail=50
📝 After Making Changes
What You Changed	Command
PHP/Blade/CSS/JS code	docker-compose restart (or just refresh browser)
.env file	docker-compose restart
Config files (config/*.php)	docker-compose exec app php artisan config:clear
Routes	docker-compose exec app php artisan route:clear
Views/Blade templates	docker-compose exec app php artisan view:clear
Clear ALL cache	docker-compose exec app php artisan optimize:clear
Database migrations	docker-compose exec app php artisan migrate
New Composer package	docker-compose build --no-cache && docker-compose up -d
Dockerfile or nginx.conf	docker-compose build && docker-compose up -d
🛠️ Artisan Commands (via Docker)
What	Command
Run migrations	docker-compose exec app php artisan migrate
Run seeders	docker-compose exec app php artisan db:seed
Clear config cache	docker-compose exec app php artisan config:clear
Clear route cache	docker-compose exec app php artisan route:clear
Clear view cache	docker-compose exec app php artisan view:clear
Clear ALL cache	docker-compose exec app php artisan optimize:clear
Cache config (prod)	docker-compose exec app php artisan config:cache
List all routes	docker-compose exec app php artisan route:list
Open Tinker (REPL)	docker-compose exec app php artisan tinker
Create controller	docker-compose exec app php artisan make:controller NameController
Create model	docker-compose exec app php artisan make:model Name
Create migration	docker-compose exec app php artisan make:migration create_table_name
Create seeder	docker-compose exec app php artisan make:seeder NameSeeder
Create middleware	docker-compose exec app php artisan make:middleware NameMiddleware
Show route list	docker-compose exec app php artisan route:list
Show migration status	docker-compose exec app php artisan migrate:status
Rollback migrations	docker-compose exec app php artisan migrate:rollback
📦 Composer Commands (via Docker)
What	Command
Install dependencies	docker-compose exec app composer install
Update dependencies	docker-compose exec app composer update
Install a package	docker-compose exec app composer require vendor/package
Remove a package	docker-compose exec app composer remove vendor/package
Dump autoload	docker-compose exec app composer dump-autoload
Show installed packages	docker-compose exec app composer show
Update single package	docker-compose exec app composer update vendor/package
🔧 Docker Management
What	Command
Full rebuild	docker-compose down && docker-compose build --no-cache && docker-compose up -d
Stop and remove everything	docker-compose down -v
Open bash inside container	docker-compose exec app bash
Check resource usage	docker stats
List all containers	docker ps -a
Remove unused containers	docker system prune
View container details	docker inspect votecast-app
🐞 Troubleshooting
Problem	Solution
App not loading	Run docker-compose ps to check status
Container keeps restarting	Run docker-compose logs app to see errors
Port 8000 already in use	Change port in docker-compose.yml: "8001:8000"
Permission errors	docker-compose exec app chmod -R 777 storage bootstrap/cache
Database connection error	Check .env DB settings, then docker-compose restart
Something weird happening	Full reset: docker-compose down -v && docker-compose up -d --build
"Composer platform check" error	You're using local PHP! Use docker-compose exec app prefix
Can't connect to database	Verify Supabase credentials in .env
Storage link not working	Run docker-compose exec app php artisan storage:link
📍 Access URLs
Service	URL
VoteCast Application	http://localhost:8000
From other devices on network	http://[YOUR-IP]:8000 (find IP with ipconfig)
⚡ Quick Workflow
Every time you turn on the laptop:
powershell
cd C:\Users\Hercules\Desktop\VoteCast
docker-compose up -d
# Open browser to http://localhost:8000
End of day / Before shutdown:
powershell
cd C:\Users\Hercules\Desktop\VoteCast
docker-compose down
After making code changes:
powershell
docker-compose restart
After adding a new Composer package:
powershell
docker-compose build --no-cache
docker-compose up -d
📝 Helper Batch Files (Save on Desktop)
start-votecast.bat
batch
@echo off
cd /d C:\Users\Hercules\Desktop\VoteCast
docker-compose up -d
timeout /t 2 /nobreak >nul
start http://localhost:8000
echo VoteCast is running!
pause
stop-votecast.bat
batch
@echo off
cd /d C:\Users\Hercules\Desktop\VoteCast
docker-compose down
echo VoteCast stopped!
pause
restart-votecast.bat
batch
@echo off
cd /d C:\Users\Hercules\Desktop\VoteCast
docker-compose restart
echo VoteCast restarted!
pause
logs-votecast.bat
batch
@echo off
cd /d C:\Users\Hercules\Desktop\VoteCast
docker-compose logs -f app
⚠️ Important Reminders
❌ Don't do this	✅ Do this instead
php artisan serve	docker-compose up -d (app already running)
php artisan migrate	docker-compose exec app php artisan migrate
composer install	docker-compose exec app composer install
php artisan tinker	docker-compose exec app php artisan tinker
🎯 Golden Rule
Always prefix PHP/Artisan/Composer commands with:

powershell
docker-compose exec app
Exceptions (no prefix needed):

docker-compose up -d

docker-compose down

docker-compose restart

docker-compose ps

docker-compose logs

📞 Quick Reference Card
powershell
# Most common commands
docker-compose up -d                    # Start app
docker-compose down                     # Stop app
docker-compose restart                  # Restart app
docker-compose ps                       # Check status
docker-compose logs -f app              # Watch logs
docker-compose exec app php artisan migrate    # Run migrations
docker-compose exec app composer install       # Install dependencies
docker-compose exec app php artisan tinker     # Open REPL



for new git:clone system

# First, start your containers
docker-compose up -d

# Then run composer inside the app container
docker-compose exec app composer install

# Or if you need to update
docker-compose exec app composer update

# Install specific package
docker-compose exec app composer require laravel/sanctum


add user admin

docker-compose exec app php artisan tinker

\App\Models\User::updateOrCreate(
    ['email' => 'jameson@votecast.edu'],
    [
        'full_name' => 'Jameson',
        'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
        'role' => 'super_admin',
        'is_active' => true
    ]
);
exit;
