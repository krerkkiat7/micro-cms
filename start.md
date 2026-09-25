#
docker-compose up -d

# 
npm run dev

# 
php artisan serve

# สำหรับการ job ทำงาน
php artisan schedule:work

# สำหรับบันทึกผลเอง : ส่วน front
php artisan front:flush-views


--------------
# job ตอน production
* * * * * cd /path/to/my-cms && php artisan schedule:run >> /dev/null 2>&1
