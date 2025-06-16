# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

The semantic of version number is 'Level.Version'. Level is for compatibility between sofwares and Version is the release number.


## [8.9] 2022-09-15

### Added
- Back office configuration parameter, autoset backoffice.url option
- Server homepage, with links to the fiscal interface, back office and password hashing
- Signature and registration date are displayed in fiscal tickets listing
- Previous and next page links on fiscal tickets listing
- /api/fiscal/import accepts Content-Encoding: zip with a zip archive in body

### Changed
- Pagination for fiscal ticket listing shows only surrounding pages then skips by steps of 50

### Fixed
- Importing non-numerical fiscal ticket sequences on a mirror
- Archiving custom fiscal ticket types
- Reject importing incorrect fiscal data on a mirror


## [8.8] 2022-01-22

### Fixed
- Concurrential access error when writing fiscal tickets
- Potential equality check issue with date and floating numbers for some systems


## [8.7] 2021-11-25

### Added
- Script to check fiscal tickets and generate a report

### Changed
- Switched from bcrypt to sha3-512 to compute new signatures

### Fixed
- Allow TariffAreaPrice with a null price
- Check for missing required fields for new records, send the correct response
- Warning when expireDate is not set in POST api/customer
- Notice about array offset for InvalidFieldException about a parameter
- Accept a ticket line without product nor label (set to empty string)
- Reject dates after year 10000 instead of blocking the database


## [8.6] 2021-06-10

### Fixed
- Infinite loop when checking equality of a self-referencing Paymentmode, which crashed when registering an already registered ticket
- Too long string values for varchar are troncated instead of crashing


## [8.5] 2021-05-20

### Added
- SCALE_TYPE_TIME (value = 3) for product scale type.
- Non-associative field definitions for type and constraints checking.

### Fixed
- Invalid data type throws InvalidFieldException and response 400 instead
- Warning and notice about wrong image creation
- Fixed some notices in routes


## [8.4] 2020-12-14

### Added
- POST /api/tax route.
- /api/option routes.
- Options are send through sync routes.

### Fixed
- POST /api/customer with the deprecated customers= format.
- Cash session search now accept dateStop formated as a date string.


## [8.3] 2020-10-20

WARNING: previous versions may display incorrect customer's balances on Z tickets. Run/bin/fixes/recomputepre8.3custbalances.php to recompute them and list inconsistencies. The balances were still correct, it's only a display bug.

### Added
- Help texts and loguout on /fiscal/
- List all ticket types on /fiscal/ outside tickets and Z tickets

### Changed
- updatedbto8.2 is now located under bin/fixes

### Fixed
- Import v7 database script, that was broken in 8.2
- Fix checksum listing
- Customer's balance computed through CashsessionAPI->summary
- Redirection to the login form when the token is outdated in /fiscal/


## [8.2] 2020-05-15

WARNING: this version includes changes to the database. Run bin/updatedbto8.2.php on each user to upgrade your databases. Please read the added and changed sections carefully.

### Added
- Archives and GPG signing. To update, either set gpg/enabled = false in your configuration file or add your signing key to GPG and update your configuration file to add the fingerprint of your key (see README.md). Also install the new required library php-gnupg on your server to make everything working.
- Revision 2 in VersionAPI, add error responses and new routes.

### Changed
- Perpetual CS is added to cash sessions. The perpetual CS is computed server-side when writing a closed cash without including it to preserve retro-compatibility, but clients should be updated to send it along the other sums.
- FiscalTicket date refers to the date of its registration instead of the referenced ticket date.
- Assigning an empty array or null does not keep an association values but clear them. To keep the actual value, do not set it in the json structure.
- Finer control when reading records from associative arrays for errors, new records and updates.

### Fixed
- ImageAPI sends a 404 response when no image is found.
- Base64 encoding of images data for non-direct rendering (other than the GET route).
- Clearing an array of associated values.
- Compatibility with PHPUnit 9.0.
- Test files are automatically added in test suites when following a naming convention.


## [8.1] - 2020-03-03

### Added
- Changelog.
- Documentation to run the API with the php standalone server (for developpement purposes).
- Tariff area http routes: writes, delete.
- Script to export fiscal tickets to a file.
- Export periods and import fiscal tickets from the web interface.
- Currency routes.
- Discount profile route: write.

### Changed
- Format of the modelId for payment mode value image.

### Fixed
- Sqlite support.
- Sqlite3 and Mysql fixes for submodels (TariffAreaPrices, PaymentModeValues...).
- Compositions, TariffArea and embedded data from struct (http calls). Mostly when deleting and recreating the same record (without an id).
- Payment mode values and returns are correctly sorted by descending values.
- Orphaned payment mode value image removal.


## [8.0-alpha11] - 2019-08-26

### Added
- Installation steps in README.md.
- Payment mode http routes: get all, get, write.
- User http route: write.
- Role http route: write.
- Cash register http routes: writes.
- Resource http routes: get, write, delete.
- CLI tool for cron tasks: export the fiscal tickets (all or for a period) and send them to a mirror.

### Fixed
- allowed_origins default value.
- Fiscal import for fiscal mirror mode, importing EOS.
- Fiscal ticket export is a bit slower but uses much less memory (prevents depleting allowed memory).
- Payments are correctly ordered in tickets.
- Allow to set a password to a new user.
- Payment mode value image handling.


## [8.0-alpha10] - 2018-10-31

### Added
- CLI tools: checksum for every files, check for duplicated entries in failure fiscal tickets.
- Fiscal: export fiscal tickets in a zipped archive.
- Fiscal mirror mode, fiscal ticket import route.
- Single user sysmodules (authentication and database access in the configuration file), now used by default.

### Fixed
- Embedded objects with Doctrine (places in floors, cash session with taxes, sales by categories, etc)
- Default configuration.


## [8.0-alpha9] - 2018-09-24

### Added
- Floor and places route: POST.

### Changed
- Write APIs returns the full object instead of just the id.
- Fiscal number for cash session is no longer bound to the sequence (prevents conflicts).


## [8.0-alpha8] - 2018-05-31

### Added
- Product http route: getAllVisible.

### Changed
- Product http route getAll including invisible ones. Use getAllVisible for the previous behaviour.
- Sync with a cash register ignores invisible products.

### Fixed
- Reporting errors when failing to write products.


## [8.0-alpha7] - 2018-04-27

### Added
- Image http routes.


## [8.0-alpha6] - 2018-03-20

WARNING: upgrading from previous versions requires a database fix. See [this wiki page](https://wiki.pasteque.org/doku.php?id=en:tech:api8-prealpha6-dbfix) for the details.

### Added
- Customer http route: update balance.
- A tariff area can be assigned to a customer (requires database fix).

### Changed
- Customer http POST accepts only one customer at once. Sending multiple customers is still there but deprecated.
- Product barcode database field is not nullable (just for consistency).

### Fixed
- Tax by categories in cash session summary (for desktop).
- Importing tariff area prices from v7.


## [8.0-alpha5] - 2018-02-21

### Added
- Pagination to ticket search route.

### Fixed
- Optimized searches.


## [8.0-alpha4] - 2018-01-23

### Fixed
- Product http routes.
- More stable ticket save.
- Routes when installed at the root of a domain.


## [8.0-alpha3] - 2018-01-16

### Changed
- Product barcode can no longer be null. It uses an empty string instead.

### Fixed
- Some CORS headers.
- Pagination of fiscal ticket listing.


## [8.0-alpha2] - 2018-01-06

### Added
- Import compositions with upgrade7 script.
- Http:
  - Product: PUT, PATCH, POST
  - Category: PUT, PATCH, POST
- A failure fiscal ticket is registered when a ticket or a z-ticket fails to be registered (with it's content and the reason of the failure).

### Changed
- Records are sorted by a static field, like dispOrder, label or such, instead of Id (or something).

### Fixed
- Discount import from v7, automatically assign a name to a discount from its rate.


## [8.0-alpha1] - 2017-12-25

Completely rewriten from scratch.

### Added
- Generic API actions: get, getByReference (if available), getAll, count, search, write, delete.
- API layer with APICaller.
- Models and APIs: Category, Tax, Product, CashRegister, Role, DiscountProfile, Discount, TariffArea, Customer, PaymentMode, CashSession (with cumulative sums), Currency, Ticket, FiscalTicket, Floor, Place, Resource, Order, Image, Option.
- APIs: Version, Login, Sync.
- Postgresql and Mysql database backend.
- Http layer with Slim:
  - Login
  - User: change password, get by name
  - Cash register: get by name/reference, search
  - Cash session: get, summary (for desktop),
  - Ticket: get by session, get, search, save
  - Customer: get top 10, write
  - Fiscal: gui read
  - Sync: everything
  - Category: get all, get, get children, write
  - Product: write
- Database upgrade script from v7 to v8 (does not include sales, includes Roles, Users, Resources, CashRegisters, Currencies, DiscountProfiles, Discounts, PaymentModes, Places and Floors, Taxes, Categories, Products except archived/deleted/compositions, TariffAreas and Customers).
- Date parsing.
- Tariff area price can have an other tax rate.

### Removed
- Every API from v7 and older.
- Models: TaxCategory (no more start date in tax), SharedTickets (should be handled client-side), Attributes, Inventories, Providers and Locations, Cash movements (should be handled client-side), Customer payments (see prepay and debt change).
- Back office.
- Credentials from WordPress, database or the configuration file (only from ini files is kept).

### Changed
- Config file in a single system-moveable ini file (no source file modification at all is required).
- Embed image in product and category.
- All prices are rounded to 5 decimals, taxed and/or untaxed prices are stored in tickets to prevent rounding issues.
- Prepay product and composition is now a flag in the product definition (no longer use dedicated categories).
- Receipts are split into Tickets and CashSession, there is no longer a common table with almost nothing in it.
- FiscalTickets are registered along Tickets and CashSession when closed, those are immutable, chained and unbound to the database structure.
- Images are stored separately and retreived through their own API. Used for Customer, PaymentMode, PaymentModeValue User, Product, Category.
- Prepayment and debt now uses the same balance. Debt is just a negative prepayment.
- Totals are now stored directly in tickets.
- Customer's balance change is now stored in tickets.
