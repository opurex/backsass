<?php
// API file checksum check.
// Public Domain, CC-0, WTFPL or anything you want.
// Licensed under GPLv3 or any later by Pasteque.

namespace Pasteque\bin;

use \Pasteque\Server\AppContext;
use \Pasteque\Server\API\ResourceAPI;
use \Pasteque\Server\Model\Resource;
use \Pasteque\Server\System\DAO\DAOCondition;

$projectRoot = dirname(dirname(__DIR__));

function path($relativePath) {
    global $projectRoot;
    return sprintf($projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
}

// File listing
$filesToCheck = [
'src/lib/AppContext.php',
'src/lib/API/APIRefHelper.php',
'src/lib/API/SyncAPI.php',
'src/lib/API/SubclassHelper.php',
'src/lib/API/CashsessionAPI.php',
'src/lib/API/APIHelper.php',
'src/lib/API/TariffareaAPI.php',
'src/lib/API/TicketAPI.php',
'src/lib/API/ResourceAPI.php',
'src/lib/API/CurrencyAPI.php',
'src/lib/API/CategoryAPI.php',
'src/lib/API/ImageAPI.php',
'src/lib/API/PlaceAPI.php',
'src/lib/API/PaymentmodeAPI.php',
'src/lib/API/UserAPI.php',
'src/lib/API/TaxAPI.php',
'src/lib/API/DiscountAPI.php',
'src/lib/API/API.php',
'src/lib/API/RoleAPI.php',
'src/lib/API/ProductAPI.php',
'src/lib/API/CustomerAPI.php',
'src/lib/API/DiscountprofileAPI.php',
'src/lib/API/CashregisterAPI.php',
'src/lib/API/FiscalAPI.php',
'src/lib/API/OrderAPI.php',
'src/lib/CommonAPI/ArchiveAPI.php',
'src/lib/CommonAPI/LoginAPI.php',
'src/lib/CommonAPI/VersionAPI.php',
'src/lib/FiscalMirrorAPI/FiscalAPI.php',
'src/lib/Model/Tax.php',
'src/lib/Model/Category.php',
'src/lib/Model/PaymentMode.php',
'src/lib/Model/DiscountProfile.php',
'src/lib/Model/Place.php',
'src/lib/Model/Image.php',
'src/lib/Model/FiscalTicket.php',
'src/lib/Model/Product.php',
'src/lib/Model/GenericModel.php',
'src/lib/Model/Customer.php',
'src/lib/Model/Discount.php',
'src/lib/Model/CashSession.php',
'src/lib/Model/CashRegister.php',
'src/lib/Model/CashSessionCustBalance.php',
'src/lib/Model/Floor.php',
'src/lib/Model/TicketLine.php',
'src/lib/Model/TariffArea.php',
'src/lib/Model/CashSessionPayment.php',
'src/lib/Model/Ticket.php',
'src/lib/Model/TicketPayment.php',
'src/lib/Model/CashSessionCat.php',
'src/lib/Model/CompositionGroup.php',
'src/lib/Model/User.php',
'src/lib/Model/Role.php',
'src/lib/Model/PaymentModeReturn.php',
'src/lib/Model/CashSessionTax.php',
'src/lib/Model/Option.php',
'src/lib/Model/TariffAreaPrice.php',
'src/lib/Model/CompositionProduct.php',
'src/lib/Model/PaymentModeValue.php',
'src/lib/Model/Resource.php',
'src/lib/Model/TicketTax.php',
'src/lib/Model/CashSessionCatTax.php',
'src/lib/Model/Currency.php',
'src/lib/Model/Field/BoolField.php',
'src/lib/Model/Field/DateField.php',
'src/lib/Model/Field/EnumField.php',
'src/lib/Model/Field/Field.php',
'src/lib/Model/Field/FloatField.php',
'src/lib/Model/Field/IntField.php',
'src/lib/Model/Field/StringField.php',
'src/lib/System/DateUtils.php',
'src/lib/System/Thumbnailer.php',
'src/lib/System/Login.php',
'src/lib/System/SysModules/SysModuleNotFoundException.php',
'src/lib/System/SysModules/SysModuleFactory.php',
'src/lib/System/SysModules/SysModuleConfigException.php',
'src/lib/System/SysModules/Ident/InifileIdent.php',
'src/lib/System/SysModules/Ident/SingleIdent.php',
'src/lib/System/SysModules/Ident/IdentModule.php',
'src/lib/System/SysModules/SysModule.php',
'src/lib/System/SysModules/Database/DBModule.php',
'src/lib/System/SysModules/Database/SingleDB.php',
'src/lib/System/SysModules/Database/InifileDB.php',
'src/lib/System/DAO/DoctrineModel.php',
'src/lib/System/DAO/DAOFactory.php',
'src/lib/System/DAO/DBException.php',
'src/lib/System/DAO/DAOCondition.php',
'src/lib/System/DAO/DAO.php',
'src/lib/System/DAO/DoctrineDAO.php',
'src/lib/System/API/APIMethodParser.php',
'src/http/middlewares/login_middleware.php',
'src/http/middlewares/cors_middleware.php',
'src/http/APIResponse.php',
'src/http/public/index.php',
'src/http/routes/user.php',
'src/http/routes/sync.php',
'src/http/routes/cashregister.php',
'src/http/routes/product.php',
'src/http/routes/role.php',
'src/http/routes/fiscal.php',
'src/http/routes/cash.php',
'src/http/routes/customer.php',
'src/http/routes/discountprofile.php',
'src/http/routes/resource.php',
'src/http/routes/place.php',
'src/http/routes/tariffarea.php',
'src/http/routes/paymentmode.php',
'src/http/routes/category.php',
'src/http/routes/tax.php',
'src/http/routes/image.php',
'src/http/routes/login.php',
'src/http/routes/version.php',
'src/http/routes/discount.php',
'src/http/routes/ticket.php',
'src/http/routes/currency.php',
'src/http/templates/footer.html',
'src/http/templates/apierror.php',
'src/http/templates/header.html',
'src/http/templates/listtkts.php',
'src/http/templates/menu.php',
'src/http/templates/listz.php',
'src/http/templates/login.php',
];

// Md5 sums computing
$md5sums = [];
$metaSum = '';
for ($i = 0; $i < count($filesToCheck); $i++) {
    $content = file_get_contents(path($filesToCheck[$i]));
    $md5 = md5($content);
    $metaSum .= $md5;
    $md5sums[] = sprintf('%s:%s', $filesToCheck[$i], $md5);
}

$superMd5 = md5($metaSum);

// Display
echo(sprintf("{\"Global checksum\": \"%s\",\n", $superMd5));
echo("\"File by file checksum\": [\n");
for ($i = 0; $i < count($md5sums); $i++) {
    $split = explode(':', $md5sums[$i]);
    if ($i != count($md5sums) - 1) {
        echo(sprintf("\"%s\": \"%s\",\n", $split[0], $split[1]));
    } else {
        echo(sprintf("\"%s\": \"%s\"]", $split[0], $split[1]));
    }
}
echo("}\n");
