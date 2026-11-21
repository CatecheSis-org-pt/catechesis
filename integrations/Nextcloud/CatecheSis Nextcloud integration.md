# CatecheSis integration with Nextcloud - Setup Guide

This document guides you in the configuration of Nextcloud with proper settings
 to integrate it with CatecheSis.
 
Throughout this document it is assumed that Nextcloud is already installed in
 the same server where CatecheSis is installed.

**The latest Nextcloud version tested and confirmed to work with CatecheSis is 35.0.3.2.** 

## Configuration


### User authentication with CatecheSis

There are two ways of integrating the CatecheSis users database with Nextcloud:
1. Using the app [User and Group SQL Backends](https://apps.nextcloud.com/apps/user_sql), which is the easiest and recommended way;
2. Using the app [User Backend Using Raw SQL](https://apps.nextcloud.com/apps/user_backend_sql_raw), which requires writing SQL queries but offers more flexibility;


#### Using *User and Group SQL Backends* ***[RECOMENDED]***

NOTE: The app `User and Group SQL Backends` is not available anymore in the Nextcloud apps store, and the [original project](https://github.com/nextcloud/user_sql) is not maintained either. We provide here a work-around, which used a [forked version](https://github.com/renaudallard/user_sql ) of it.

1. Run the SQL script `./User authentication/SQL user extension/create_view.sql` in your database, which replicates the CatecheSis users on Nextcloud. If you want to reduce the Nextcloud users only to those curretnly active in CatecheSis, use one of the alternate queries in [`./User authentication/SQL user extension/queries`](./User authentication/SQL user extension/queries) instead.
2. Clone the following Git repo into your Nextcloud apps folder: https://github.com/renaudallard/user_sql
3. Login to Nextcloud as admin and, through the Nextcloud Apps GUI, force the activation of this app (it is reported as incompatible with Nexctloud 35, so you must force it).
4. ~~In Nextcloud GUI go to *Settings > SQL Backends*. Configure the fields as shown in the images below 
   (adapt the database name, username and password to your CatecheSis database):~~ **_See note below_**

![Settings](User%20authentication/SQL%20user%20extension/Settings_1.png)
![Settings](User%20authentication/SQL%20user%20extension/Settings_2b.png)


> NOTE: At the time of writing of this tutorial, it looks like the *SQL Backends* settings page is not working on Nextcloud 35. Pushing the buttons *Verify settings* or *Save* leads to an `HTTP Error 405 Method Not Allowed`. As a work-around, you can set these settings through `INSERT` queries to the database.

To manually configure these settings, use the following SQL query, replacing `<YOUR_CATECHESIS_DATABASE>`, `<YOUR_DB_USER_PASSWORD>`and `<YOUR_DB_USER>` appropriately.
For the database user, you can use the Nextcloud's database user and give it read (i.e. SELECT) permissions on the CatecheSis database, or you can use the CatecheSis' database user which already has permissions on it (but probably has more permissions than needed by Nextcloud).

```SQL
INSERT INTO `oc_appconfig` (`appid`, `configkey`, `configvalue`) VALUES
('user_sql', 'db.database', '<YOUR_CATECHESIS_DATABASE>'),
('user_sql', 'db.driver', 'mysql'),
('user_sql', 'db.hostname', 'localhost'),
('user_sql', 'db.password', '<YOUR_DB_USER_PASSWORD>'),
('user_sql', 'db.ssl_ca', ''),
('user_sql', 'db.ssl_cert', ''),
('user_sql', 'db.ssl_key', ''),
('user_sql', 'db.table.group', ''),
('user_sql', 'db.table.group.column.admin', ''),
('user_sql', 'db.table.group.column.gid', ''),
('user_sql', 'db.table.group.column.name', ''),
('user_sql', 'db.table.user', 'nextcloud_users'),
('user_sql', 'db.table.user.column.active', 'active'),
('user_sql', 'db.table.user.column.avatar', ''),
('user_sql', 'db.table.user.column.disabled', ''),
('user_sql', 'db.table.user.column.email', 'email'),
('user_sql', 'db.table.user.column.home', ''),
('user_sql', 'db.table.user.column.name', 'display_name'),
('user_sql', 'db.table.user.column.password', 'password'),
('user_sql', 'db.table.user.column.quota', ''),
('user_sql', 'db.table.user.column.salt', ''),
('user_sql', 'db.table.user.column.uid', 'username'),
('user_sql', 'db.table.user.column.username', 'username'),
('user_sql', 'db.table.user_group', ''),
('user_sql', 'db.table.user_group.column.gid', ''),
('user_sql', 'db.table.user_group.column.uid', ''),
('user_sql', 'db.username', '<YOUR_DB_USER>'),
('user_sql', 'opt.crypto_class', 'OCA\\UserSQL\\Crypto\\CryptBlowfish'),
('user_sql', 'opt.crypto_param_0', '11'),
('user_sql', 'opt.default_group', 'catequistas'),
('user_sql', 'opt.email_sync', 'force_sql'),
('user_sql', 'opt.home_location', ''),
('user_sql', 'opt.home_mode', ''),
('user_sql', 'opt.name_sync', 'force_sql'),
('user_sql', 'opt.quota_sync', ''),
('user_sql', 'opt.safe_store', '0');
```



#### Using *User Backend Using Raw SQL*

1. Install the app [User Backend Using Raw SQL](https://apps.nextcloud.com/apps/user_backend_sql_raw) through the Nextcloud apps store.
2. Add to the Nextcloud `config.php` the following lines:

```php
...
  'user_backend_sql_raw' => array(
	'db_type' => 'mariadb',
	'db_host' => 'localhost',
	'db_port' => '3306',
	'db_name' => '<YOUR_DB>',
	'db_user' => '<YOUR_USER>',
	'db_password' => '<PASSWORD>',
	//'mariadb_charset' => 'utf8mb4',
	'queries' => array(
		'get_password_hash_for_user' => 'SELECT password FROM ul_logins WHERE username = :username',
		'user_exists' => 'SELECT EXISTS(SELECT 1 FROM ul_logins WHERE username = :username)',
		'get_users' => 'SELECT l.username FROM ul_logins l, utilizador u WHERE (l.username LIKE :search) OR (u.nome LIKE :search)',
		//'set_password_hash_for_user' => 'UPDATE users SET password_hash = :new_password_hash WHERE local = split_part(:username, \'@\', 1) AND domain = split_part(:username, \'@\', 2)',
		//'delete_user' => 'DELETE FROM users WHERE local = split_part(:username, \'@\', 1) AND domain = split_part(:username, \'@\', 2)',
		'get_display_name' => 'SELECT u.nome FROM utilizador u WHERE username = :username'
		//'set_display_name' => 'UPDATE users SET display_name = :new_display_name WHERE local = split_part(:username, \'@\', 1) AND domain = split_part(:username, \'@\', 2)',
		//'count_users' => 'SELECT COUNT * FROM ul_logins'
		//'get_home' => '',
		//'create_user' => 'INSERT INTO users (local, domain, password_hash) VALUES (split_part(:username, \'@\', 1), split_part(:username, \'@\', 2), :password_hash)',
	)
	//'hash_algorithm_for_new_passwords' => 'bcrypt',
)
...
```

Replace by your CatecheSis database name, username and password.




### Set default user language and locale

Add to the `config.php` file the following lines:

```php
"default_language" => "pt_PT",
"force_language" => "pt_PT",
"default_locale" => "pt_PT",
"force_locale" => "pt_PT",
'default_phone_region' => 'PT',
```

The options `default_...` set the language/locale only when the browser does not specify one.
The options `force...` override the language/local and do not allow the users to change them.


### Set default user quota

Log in to Nextcloud with an administrator account.
Head to Users settings, click the gears button in the bottom left corner of the screen, and set there the default user quota.


### Create 'Everyone' group

The app [Everone group](https://apps.nextcloud.com/apps/group_everyone) creates a virtual group that automatically 
includes all the users. With this app, you don't need to manually assign each user to the "everyone" group, and every 
time a new user is created it is automatically joined in the group.


### Create folders shared with everyone

From within the administrator account, create some folders and share them with 'Everyone' group.
That way, catechists can easily share files with the whole group.

~~The [Group folders app](https://apps.nextcloud.com/apps/groupfolders) facilitates this task, and
has the advantage of creating shared folders with a specific quota (which can be set to unlimited).~~ The group folders are now a feature built in Nextcloud, so there is no need to install any app.

That way, you can set a folder without any quota where catechists can share everything freely,
while each catechist still has a quota on the amount of files they can store in their personal accounts. 

Suggested folder structure to share with everyone:

```
.
└── Courseware
    ├── Adolescence
    ├── General
    ├── Infancy
    └── Virtual catechesis resources
```

At the top of each folder, in the web interface, it is possible to write a small description in Markdown
 to explain the users the intended usage cases for each folder.


### Create public folder *'Virtual catechesis resources'*

Create a folder to store virtual catechesis resource files, to be shown inside the virtual catechesis editor in CatecheSis.
Share this folder with a public link, with read-only permissions.
Then, set the following two lines in the CatecheSis config file (`catechesis_config.inc.php`):

```php
...
//Nextcloud integration
//  Comment out the following lines if you don't plan to integrate CatecheSis with Nextcloud.
define('CATECHESIS_NEXTCLOUD_BASE_URL', 'https://<YOUR_DOMAIN>/cloud');                                             //Path to Nextcloud front page
define('CATECGESIS_NEXTCLOUD_VIRTUAL_RESOURCES_URL', 'https://<YOUR_DOMAIN>/cloud/index.php/<NEXTCLOUD_LINK>');     //Path to public shared folder in Nextcloud to store virtual catechesis resources
...
```

The first line sets the URL for the Nextcloud front page (for when users click on a button to go to the cloud).
The second line defines the public URL of the shared folder to be shown in the virtual catechesis editor, when the user 
clicks the button to include resources from the cloud.

If you want to allow all catechists to contribute to this folder with new resources, we suggest that you create 
it inside the *Courseware* folder suggested in the previous step and shared with the group *Everyone*.



### Disable user password recovery link on front page

Add to the `config.php` file the following line:

```php
'lost_password_link' => 'disabled',
```

Users must use CatecheSis to manage their credentials.



### Disable php output buffering

Edit the `.htaccess` file in the root folder of your Nextcloud installation and add the following line in the end of the
file:

```.htaccess
php_value output_buffering 0
```

*Note: Some online documentation suggests to write `php_value output_buffering Off`, which should be equivalent. However,
due to a bug in Nextcloud, it only recognizes the property (and disables the corresponding warning) if you set the
it with value `0` instead of `Off`.*



### Configure APCu local cache

Make sure the APCu cache is enabled in your server's PHP settings.
Add to the `config.php` file the following line:

```php
'memcache.local' => '\OC\Memcache\APCu', 
```

### Configure the server maintenance window

Set a time of the day when the server usually has no use, so that Nextcloud can preform longer maintenance tasks at that time window (starting from that time + 4 hours).
For example, to set to 2am, add to the `config.php` file the following line:

```php
'maintenance_window_start' => 2,
```

### Configure cron jobs

Certain background routines in Nextcloud must run periodically, for example to trigger e-mails and cleanup tasks.
By default, they are triggered when a user interacts with pages in Nextcloud. However, this may not be sufficient.
The recommended way to go is to setup a cronjob.

At Cova da Piedade, we set up two cronjobs. On Saturdays, it runs every 15 minutes, since this is the day most users use
Nextcloud. On the remaining week days, it runs every hour.

```cron
0 * * * 0-5 /usr/local/bin/php -f /home/catechesis/public_html/cloud/cron.php --define apc.enable_cli=1 >/dev/null 2>&1
*/15 * * * 6 /usr/local/bin/php -f /home/catechesis/public_html/cloud/cron.php --define apc.enable_cli=1 >/dev/null 2>&1
 ```

<br>

## Optional steps and recommendations

### Useful apps to install

##### [External sites](https://apps.nextcloud.com/apps/external)

This app allows the inclusion of links to external sites in the Nextcloud menu bar.
You can use it to add a link to CatecheSis.


##### [Bookmarks](https://apps.nextcloud.com/apps/bookmarks)

This app adds a new icon in the Nextcloud menu bar and file explorer to store and organize links.
We suggest you create a folder inside *Bookmarks* and share it with *Everyone*. 
Then, add some useful links to courseware available online in your parish or country.  


##### [Calendar](https://apps.nextcloud.com/apps/calendar)

With this app you can create a catechesis calendar and share it with your catechists, through the *Everyone* group.
Put there relevant dates for your parish (for example, baptisms, catechists meetings, etc.).
It is possible to sync this calendar with desktop/mobile mail clients and calendar apps, and it is even possible to 
embed it in websites.


##### [Extract](https://apps.nextcloud.com/apps/extract)

Allows the extration of compressed files (such as .zip) through the web interface.
This way, your users can benefit from faster upload times by uploading compressed files/folders and decompressing them
on the server.


##### [Link editor](https://github.com/te-online/files_linkeditor)

Allows the creation of links in any foler, as any other file.
When clicked, the link file shows the URL and prompts to open it in a browser tab.
This may be an alternative to the *Bookmarks* app to share links between catechists.


##### [Right click](https://github.com/nextcloud/files_rightclick)

Adds a context menu when user right click in the file explorer.


##### [Sharing path](https://apps.nextcloud.com/apps/sharingpath)

This app allows to use Nextcloud as a Content Delivery Network (CDN).
It may be useful to serve files (such as videos) to be embeded in virtual catechesis.

To generate a URL to a file, first you need to share it through the regular Nextcloud share options to generate a public
URL. Then, right-click on top of the file and select *Open Sharing Path* to generate a direct URL to the file, ready to
embed in a website.


##### [Temporary files lock](https://apps.nextcloud.com/apps/files_lock)

Adds a right-click menu entry to lock files for editing.





### Disable these default (pre-installed) apps

The following apps may be disabled, since these functionalities are not integrated with CatecheSis and could thus deceive
the users. They are also probably not needed.

```
contactsinteraction
user_ldap
user_status
weather_status
```


### Reference `occ app:list` output from Cova da Piedade

The following output from `occ app:list` command lists the enabled and disabled apps in Cova da Piedade's Nextcloud.

```
Enabled:
  - accessibility: 1.6.0
  - activity: 2.13.4
  - bookmarks: 4.0.8
  - bruteforcesettings: 2.0.1
  - calendar: 2.1.3
  - cloud_federation_api: 1.3.0
  - comments: 1.10.0
  - dashboard: 7.0.0
  - dav: 1.16.2
  - external: 3.7.2
  - extract: 1.3.0
  - federatedfilesharing: 1.10.2
  - federation: 1.10.1
  - files: 1.15.0
  - files_linkeditor: 1.1.3
  - files_lock: 20.0.0
  - files_pdfviewer: 2.0.1
  - files_rightclick: 0.17.0
  - files_sharing: 1.12.2
  - files_trashbin: 1.10.1
  - files_versions: 1.13.0
  - files_videoplayer: 1.9.0
  - firstrunwizard: 2.9.0
  - group_everyone: 0.1.6
  - groupfolders: 8.2.0
  - hsts: 0.8.0
  - logreader: 2.5.0
  - lookup_server_connector: 1.8.0
  - nextcloud_announcements: 1.9.0
  - notifications: 2.8.0
  - oauth2: 1.8.0
  - password_policy: 1.10.1
  - photos: 1.2.3
  - privacy: 1.4.0
  - provisioning_api: 1.10.0
  - recommendations: 0.8.0
  - serverinfo: 1.10.0
  - settings: 1.2.0
  - sharebymail: 1.10.0
  - sharingpath: 0.3.0
  - support: 1.3.0
  - systemtags: 1.10.0
  - text: 3.1.0
  - theming: 1.11.0
  - twofactor_backupcodes: 1.9.0
  - updatenotification: 1.10.0
  - user_sql: 4.6.0
  - viewer: 1.4.0
  - workflowengine: 2.2.0
Disabled:
  - admin_audit
  - contactsinteraction
  - encryption
  - files_external
  - survey_client
  - user_ldap
  - user_status
  - weather_status
```


### Create some file tags

You could create some tags for your catechists to easily tag files in categories and make them easier to find.
Ideas for some usefull tags: `infancy`, `adolescence`, `1st catechism`, `2nd catechism`, ...


### Change the default files for new user accounts

You can change the sample files/folders that are provided by default to each user account.
Put these files in a directory readable by the web server user, and set its path in the Nextcloud `config.php`:

```php
'skeletondirectory' => '/path/to/nextcloud/core/skeleton',
```

Leave empty to not copy any skeleton files.

Defaults to `core/skeleton` in the Nextcloud directory. 
*NOTE: Do not change the contents of this folder directly, since
it will be reset on each Nextcloud upgrade.*


### Customize theme colors and icons

It is possible to customize several aspects of Nextcloud, namely the name (shown on the browser tab title and e-mails),
the primary color, the application icon and favicon, and slogan.

Login as admin and go to *Settings > Theming*.


### Configure e-mail SMTP settings

Configure the SMTP in *Settings > Basic settings > Email server* so that Nextcloud can send e-mails to users.
