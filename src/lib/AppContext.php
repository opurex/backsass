<?php
//    Pastèque Web back office
//
//    Copyright (C) 2013 Scil (http://scil.coop)
//
//    This file is part of Pastèque.
//
//    Pastèque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pastèque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pastèque.  If not, see <http://www.gnu.org/licenses/>.

namespace Pasteque\Server;

use \Monolog\Logger;
use \Monolog\Handler\ErrorLogHandler;
use \Monolog\Handler\StreamHandler;
use \Pasteque\Server\System\DAO\DAOFactory;
use \Pasteque\Server\System\SysModules\SysModuleFactory;
use \Pasteque\Server\System\SysModules\SysModuleNotFoundException;
use \Pasteque\Server\System\Thumbnailer;

class AppContext {

    const MODE_REGULAR = 0;
    const MODE_FISCALMIRROR = 1;

    private $allowedOrigin;
    private $defaultBackOfficeUrl;
    private $jwtSecret;
    private $jwtTimeout;
    private $identModule;
    private $dbModule;
    private $logger;
    private $currentUser;
    private $dbInfo;
    private $thumbnailer;
    private $isDebugMode;
    private $serverMode;
    private $gpgEnabled;
    private $gpgPath;
    private $dao;

    public function __construct() {
        $this->jwtSecret = '';
        $this->jwtTimeout = 600;
        $this->logger = new Logger('pasteque');
        $this->isDebugMode = false;
        $this->allowedOrigin = '';
        $this->gpgEnabled = true;
        $this->serverMode = static::MODE_REGULAR;
    }

    public static function loadFromConfig($config) {
        $app = new AppContext();
        // Configure logger
        $log = new Logger('pasteque');
        $level = Logger::WARNING;
        switch (strtoupper($config['log_level'])) {
            case 'DEBUG': $level = Logger::DEBUG; break;
            case 'INFO': $level = Logger::INFO; break;
            case 'WARN': // nobreak
            case 'WARNING': $level = Logger::WARNING; break;
            case 'ERR': // nobreak
            case 'ERROR': $level = Logger::ERROR; break;
        }
        if (!empty($config['log_dir'])) {
            $date = date('Ymd');
            $path = $config['log_dir'] . '/' . $date . '.log';
            $log->pushHandler(new StreamHandler($path, $level));
        }
        $log->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $level));
        $app->setLogger($log);
        $app->setDebugMode($config['debug']);
        // Configure thumbnailer
        $thumbnailer = new Thumbnailer();
        if (!empty($config['thumbnail/width'])) {
            $thumbnailer->setOutputWidth($config['thumbnail/width']);
        }
        if (!empty($config['thumbnail/height'])) {
            $thumbnailer->setOutputHeight($config['thumbnail/height']);
        }
        if (!empty($config['thumbnail/format'])) {
            $thumbnailer->setOutputFormat($config['thumbnail/format']);
        } else {
            $thumbnailer->setOutputFormat(Thumbnailer::FORMAT_ORIGINAL);
        }
        if (!empty($config['thumbnail/jpeg/quality'])) {
            $thumbnailer->setOutputJpegQuality($config['thumbnail/jpeg/quality']);
        }
        if (!empty($config['thumbnail/png/compression'])) {
            $thumbnailer->setOutputPngCompression($config['thumbnail/png/compression']);
        }
        if (!empty($config['thumbnail/width']) && !empty($config['thumbnail/height'])) {
            $app->setThumbnailer($thumbnailer);
        }
        // Load core modules
        $identModule = SysModuleFactory::getIdentModule($config['core_ident'],
                SysModuleFactory::extractIdentModuleConfig($config, $config['core_ident']));
        $dbModule = SysModuleFactory::getDatabaseModule($config['core_database'],
                SysModuleFactory::extractDatabaseModuleConfig($config, $config['core_database']));
        $app->setIdentModule($identModule);
        $app->setDbModule($dbModule);
        // JWT and links options
        $app->setJwtSecret($config['jwt_secret']);
        $app->setJwtTimeout($config['jwt_timeout']);
        if (!empty($config['allowed_origin'])) {
            $app->setAllowedOrigin($config['allowed_origin']);
        }
        if (!empty($config['default_backoffice'])) {
            $app->setDefaultBackOfficeUrl($config['default_backoffice']);
        }
        // Signing options
        if (array_key_exists('gpg/enabled', $config)
                && $config['gpg/enabled'] === false) {
            $app->enableGPG(false);
        } else {
            $path = $config['gpg/path'];
            $basePath = dirname(dirname(dirname(__FILE__)));
            if (substr($path, 0, 1) == '.') {
                $app->setGPGPath($basePath . '/' . $path);
            } else {
                $app->setGPGPath($path);
            }
            $app->setKeyFingerprint($config['gpg/fingerprint']);
        }
        // Misc options
        if (!empty($config['server_mode'])) {
            switch (trim(strtolower($config['server_mode']))) {
            case 'fiscal mirror':
            case 'fiscal_mirror':
                $app->setServerMode(static::MODE_FISCALMIRROR);
                break;
            }
        }
        return $app;
    }

    public function getJwtSecret() { return $this->jwtSecret; }
    public function setJwtSecret($secret) { $this->jwtSecret = $secret; }
    public function getJwtTimeout() { return $this->jwtTimeout; }
    public function setJwtTimeout($timeout) { $this->jwtTimeout = $timeout; }
    public function setDefaultBackOfficeUrl($url) {
        $this->defaultBackOfficeUrl = $url;
    }
    public function getDefaultBackOfficeUrl() {
        return $this->defaultBackOfficeUrl;
    }
    public function getIdentModule() { return $this->identModule; }
    public function setIdentModule($module) { $this->identModule = $module; }
    public function getDbModule() { return $this->dbModule; }
    public function setDbModule($module) { $this->dbModule = $module; }
    public function getLogger() { return $this->logger; }
    public function setLogger($logger) { $this->logger = $logger; }
    public function isDebugMode() { return $this->isDebugMode; }
    public function setDebugMode($mode) { $this->isDebugMode = $mode; }
    public function getThumbnailer() { return $this->thumbnailer; }
    public function setThumbnailer($thumbnailer) { $this->thumbnailer = $thumbnailer; }
    public function isGPGEnabled() { return $this->gpgEnabled; }
    public function enableGPG($gpg) { $this->gpgEnabled = $gpg; }
    public function getGPGPath() { return $this->gpgPath; }
    public function setGPGPath($path) { $this->gpgPath = $path; }
    public function getKeyFingerprint() { return $this->keyFingerprint; }
    public function setKeyFingerprint($fp) { $this->keyFingerprint = $fp; }
    public function getCurrentUser() { return $this->currentUser; }
    public function getAllowedOrigin() { return $this->allowedOrigin; }
    public function setAllowedOrigin($origins) { $this->allowedOrigin = $origins; }
    public function isFiscalMirror() { return $this->serverMode == static::MODE_FISCALMIRROR; }
    public function setServerMode($mode) {
        $this->serverMode = $mode;
    }

    /** Set current user and it's dbInfo.
     * @param $user User data returned by the IdentModule. */
    public function login($user) {
        $this->currentUser = $user;
        $this->dbInfo = $this->dbModule->getDatabase($user['id']);
        $this->dao = DAOFactory::getDAO($this->getDbInfo(),
                array('debug' => $this->isDebugMode()));
    }

    public function getDbInfo() { return $this->dbInfo; }
    public function getDao() { return $this->dao; }
}
