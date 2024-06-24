## About Laravel Food Delivery Backend

Laravel Food Delivery Backend is an API for a Flutter application that developed for course [FIC Batch 18 Fullstack Flutter Laravel – Membangun Aplikasi Food Delivery Order dan Live Tracking – User, Resto & Driver App](https://jagoflutter.com/courses/fic-batch18/)


### Technology used

<ul>
    <li>
        <a href="https://laragon.org/download/index.html" target="_blank">Laragon 6.0.0</a>.
    </li>
    <li>
        <a href="https://www.php.net/" target="_blank">PHP 8.3.8</a>.
    </li>
    <li>
        <a href="https://downloads.mysql.com/archives/community/" target="_blank">MySQL 8.0.30</a>.
    </li>
     <li>
        <a href="https://www.apachelounge.com/download/" target="_blank">Apache 2.4.59</a>.
    </li>
    <li>
        <a href="https://laravel.com/docs/11.x/" target="_blank">Laravel 11.11.1</a>.
    </li>
</ul>

## Installation

Create a new project folder, cd into the folder

`git clone https://github.com/FadhilPrawira/laravel-food-delivery-backend-fadhilprawira.git`

`cp .env.example .env`

Make the needed changes regarding database connection, faker locale, timezone. By default it use timezone in Asia/Jakarta (UTC+7).

`composer install`

`php artisan key:generate`

`php artisan storage:link`

`php artisan migrate`

To run, use `php artisan serve`

Postman documentation included on folder `postman`

## Contact me

<a href="https://www.linkedin.com/in/fadhilprawira/"><img src="https://img.shields.io/badge/LinkedIn-0077B5?style=for-the-badge&logo=linkedin&logoColor=white" /></a> 

<a href="https://github.com/FadhilPrawira/"><img src="https://img.shields.io/badge/GitHub-100000?style=for-the-badge&logo=github&logoColor=white" /></a>
