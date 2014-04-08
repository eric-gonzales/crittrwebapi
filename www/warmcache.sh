cd /var/www/html/www
echo "Box Office $1"
php index.php movies boxoffice jR7Y2WLmzo 50 $1 > /dev/null
echo "Opening $1"
php index.php movies opening jR7Y2WLmzo 50 $1 > /dev/null
echo "Upcoming $1"
php index.php movies upcoming jR7Y2WLmzo 50 $1 > /dev/null
echo "New Release DVDs $1"
php index.php movies newreleasedvds jR7Y2WLmzo 50 1 $1 > /dev/null
echo "Current DVDs $1"
php index.php movies currentdvds  jR7Y2WLmzo 50 1 $1 > /dev/null
echo "Upcoming DVDs $1"
php index.php movies upcomingdvds jR7Y2WLmzo 50 1 $1 > /dev/null

