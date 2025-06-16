# Pasteque Server
> https://www.pasteque.org

## Presentation

Pasteque API is a POS (point of sale) project under the [GNU General Public License v3][gnu].
It is made in [PHP] with [PDO], [MySQL] and [PostgreSQL]. It is the backbone of the Pasteque suite.

## Installation


### Prerequisites

Please install the following packages (for a Debian 10 based system)

PHP (at least 7.0): `php php-cli php-gd php-intl php-xml php-mbstring php-zip php-gnupg`

With Apache: `apache2 libapache2-mod-php`

With Postgresql (recommended): `postgresql postgresql-client php-pgsql`, you will require to set `md5` authentication for pasteque users instead of `peer`

With MariaDB (MySQL, slower): `mariadb-server-10.1 mariadb-client-10.1 php-mysql`

### From a release package

Download a release package from https://downloads.pasteque.org/api/ or any other reliable source. These packages has all static files already generated to ease the installation process a little.

Just uncompress the archive and go to `Configuration` below.

### From source

Clone the repository to access to the source files. The repository doesn't embed generated files and requires more steps to intall a development instance.

First install dependencies with composer.

Install a database with Doctrine. Put the following file in the root directory and name it `cli-config.php`

```
<?php
namespace Pasteque\Server;
use \Pasteque\Server\System\DAO\DoctrineDAO;

$cfg = parse_ini_file(__DIR__ . '/config/test-config.ini');
$dbInfo = ['type' => $cfg['database/type'], 'host' => $cfg['database/host'],
    'port' => $cfg['database/port'], 'name' => $cfg['database/name'],
    'user' => $cfg['database/user'], 'password' => $cfg['database/password']];
$dao = new DoctrineDAO($dbInfo, ['debug' => false]);
return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($dao->getEntityManager());
```

Then copy `/config/test-config-sample.ini` to `/config/test-config.ini`. Edit this file to point to the empty database to install.

Use `php vendor/bin/doctrine orm:schema-tool:update --force` to install the database structure. No data are provided yet.

Generate proxies with `php vendor/bin/doctrine orm:generate-proxies` then make the directory `src/generated` writeable by your web server user.

### Configuration

Once all the files are installed, copy `/config/config-sample.ini` to `config/config.ini` and edit it to your liking.

For a single-user instance, you can edit all the `ident/single/*` and `database/single/*`entries to your liking.

For a shared instance, switch `core_ident` and `core_database` to `inifile`. The ini files shares the same structure as the previous entries, without the prefixes ident/single and database/single (i.e. only password= and type=, host= etc).

Also set `jwt_secret` to some random string.

Make sure the web server user can read the config file and the user ini files.

#### Database initialization

To initialize the database, use the sql scripts in `res/database/<type>/schema.sql`. The default data required for a running instance are not yet included, you can find a script at https://downloads.pasteque.org/api/scripts/

#### GPG initialization

GPG signing is required to conform to the LF2016. It enables signing archives to make sure they cannot be modified when stored outside Pasteque.

To disable GPG, set `gpg/enabled` to `false` (for testing purposes or when conformity is not required) in your configuration file. When disabled, archive generation is not available.

GPG must be installed. Check [GnuPG website](https://gnupg.org/download/index.html) if it is not already installed.

Then you should create a dedicated key for signing archives. PHP requires the key to have no passphrase to be able to generate archives automatically. Check [guides](https://gnupg.org/documentation/guides.html) on GnuPG's website again to create or import one.

For example to create a key in a dedicated keyring:

```
gpg --homedir <pasteque>/config/gpg --full-generate-key
```

or to import them

```
gpg --homedir <pasteque>/config/gpg --import <public key file>
gpg --homedir <pasteque>/config/gpg --import <secret key file>
```

If everything went well, you should see your key and it's fingerprint with

```
gpg --homedir <pasteque>/config/gpg --list-keys
```

In your configuration file put the fingerprint of your key in `gpg/fingerprint`. If you used an other homedir, you must also change `gpg/path`.

Archives are not generated from http requests as they would probably not fit in the limit of the web server. Set a cron task to run `php bin/createarchive.php <user id>` to generate archives outside the web server or run the script manually.

#### Web server configuration

##### Apache

Make your web server point to `/src/http/public`. Make sure `AllowOverride All` is set for this directory for the `.htaccess` in there to work. To be able to accept references with `/` inside them, allow encoded urls. In your site-enabled config file under `VirtualHost` put:

~~~
<Directory /path/to/pasteque/src/http/public>
	AllowOverride All
</Directory>
AllowEncodedSlashes NoDecode
~~~

Also check that the rewrite mod is enabled, it should be listed in the `mods-enabled` directory of your Apache server.

You should now call an API: `http(s)://<url_to_pasteque>/api/<route>`

Check your installation by requesting `http(s)://<url_to_pasteque>/fiscal/` you should be prompted to login, and you should be able to log if the user and database are correctly set.

##### PHP standalone server

This method is not recommended but could help in certain cases, mostly for developpement and debugging. Or when no server at all could be installed.

Start the PHP standalone server running `src/http/public/index.php` with the command `php -S <host>:<port> src/http/public/index.php`. Like `php -S localhost:8080 src/http/public/index.php` or `php -S 192.168.1.0:8080 src/http/public/index.php`.

### Install clients

Once the API is up and running, all is required now is some clients to connect to it. Pick a cash register client (Desktop or Android) and a backoffice client (admin or jsadmin) and you are ready to go.

## Contributing

### Software architecture

```
/bin         Some php cli scripts to manage your instance.
/config      All the ini files, copy and edit the samples to configure your server.
/res         Business data, like default data and database schemes.
/src         The code of Pasteque server.
/src/lib     Internal code of Pasteque
/src/http    External access (HTTP).
/src/http/public This is the directory your web server must point to.
/tests       The tests of Pasteque server, see tests/README to run the tests
/vendor      Third party libraries. Use composer to install them.
```

### Internal code

Relative to /src/lib

```
/API                The API classes and methods. This is what can be called from outside.
/Model              The model declarations with annotations.
/System             The non-business underlying code to run the API.
/System/API         The magic under API calls lays here.
/System/SysModules  How to read user login and database credentials.
/System/DAO         The code relative to DAO (the Doctrine layer)
AppContext          Read from the config file to run the server from external code.
```

### External code

Relative to /src/http.

```
/middlewares   Slim middlewares, including token management.
/public        The files opened to public access. Contains index.php and .htaccess.
/routes        Slim routes, one file per api
```

All routes are documented with Swagger. They are called through a given URL which contains the parameters.

Routes then make a internal call and transform the response back to HTTP. The routes must be all defined explicitely and annotated for Swagger to be able to generate the documentation.

### How do I

#### Call an API from the external layer

An API call is made from \Pasteque\Server\System\API\APICaller::run. It takes the application context, the class name, the method name and arguments as parameters.

All the public methods from all classes from \Pasteque\Server\API namespace can be requested. Parameters can either be a flat array like a regular method call, or a named parameter call with an associative array.

You can call getToken($user, $password) from LoginAPI either with APICaller::run($app, 'login', 'getToken', ['myLogin', 'MyPassword']) or APICaller::run($app, 'login', 'getToken', ['password' => 'myPassword', 'user' => 'myLogin']).

The API name is case insensitive and the suffix "API" from the class name can be ommited. $app is the AppContext initialized in the external layer and shared across the layer.

It will return a \Pasteque\Server\System\API\APIResult with it's status and object content. To pass the content to the outside, use $response->getStructContent() or $response->toJson() to obtain raw data and not DAO-proxied objects.


#### Call an API from outside

Look at the documentation for the URL to provide to the server. Authenticate with the login api and pass the fetched token for further calls. The HTTP layer put the token in a cookie to ease access. You can also use GET, POST or HTTP header to pass it.

#### Create an API

Add a new class which name ends by "API" and must have only the first letter capitalized into /src/lib/API, set the desired methods public and that's it. The API layer will magically map your code, do the parameters binding and format the response from and for the external layer.

The API classes takes a DAO object from constructor (see /src/lib/System/DAO/DAO.php) to access to the database. It is also provided by the API layer.

The method opened to the outside must then be mapped by a route un Slim.

#### Edit a model

The models inside /src/lib/Model has annotations for Swagger and Doctrine. When editing a model, the fields will be automatically mapped by the DAO. Doctrine requires to declare attributes protected and add a getter and a setter. You will also have to list the primitive-typed fields and reference fields from both static methods getDirectFieldNames and getAssociationFieldNames. This is required to generate raw data output for the external layer because Doctrine uses proxies for each model attribute.

If the editions have an impact on the behaviour of the API, you will require to edit the corresponding API in /src/lib/API. Otherwise it will be automatically passed to the output and can be searchable without any further edit.

#### Add internal features

For all the things relative to the database, see /src/lib/System/DAO: DAO.php for the general interface and DoctrineDAO.php for the implementation. The actual DAO instance is provided by DAOFactory to the API layer.

The database access is provided by a system module. See /src/lib/System/SysModules/Database/DBModule.php for the general interface. The implementations are provided by SystModuleFactory. This is the same scheme for account data with IdentModule.

The AppContext will glue all these tools to pass them afterward to the API. Given an ini configuration file, it will fetch the corresponding system modules and DAO.

### Swagger and OAS

We use Open API Specification (OAS, see <https://www.openapis.org/>) and <http://swagger.io> tools to generate the API documentation.

The JSON file is located in /bin/swagg.json

You can generate it with the script `generate_swagger_file.php`. The JSON /bin/swagg.json will get updated

    $ php generate_swagger_file.php

You can use <http://petstore.swagger.io> and submit your own swagg.json file or you can either install swagger-UI, read the docs at <https://swagger.io/docs/swagger-tools/#swagger-ui-documentation-29>
# backsass
