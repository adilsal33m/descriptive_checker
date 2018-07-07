<?php
require_once __DIR__ . '\..\autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class FirebaseHelper
{
    /** @var View View The view object */
    public static $firebase;

    /**
     * Construct the (base) controller. This happens when a real controller is constructed, like in
     * the constructor of IndexController when it says: parent::__construct();
     */
    public function __construct()
    {
    }

    public static function init()
    {
        //Initialize Firebase
        $serviceAccount = ServiceAccount::fromJsonFile('../application/descriptive-checker-firebase-adminsdk-5df5h-c83dc2c9fc.json');
        FirebaseHelper::$firebase = (new Factory)
        ->withServiceAccount($serviceAccount)
        // The following line is optional if the project id in your credentials file
        // is identical to the subdomain of your Firebase project. If you need it,
        // make sure to replace the URL with the URL of your project.
        ->withDatabaseUri('https://descriptive-checker.firebaseio.com/')
        ->create();
    }
}
?>
