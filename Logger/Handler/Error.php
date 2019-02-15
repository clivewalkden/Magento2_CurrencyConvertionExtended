<?php
/**
 * SOZO Design
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    SOZO Design
 * @package     Sozo_ProductFeatures
 * @copyright   Copyright (c) 2019 SOZO Design (https://sozodesign.co.uk)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */

namespace Sozo\CurrencyConversionExtended\Logger\Handler;

use Monolog\Logger;

class Error extends HandlerAbstract
{
    /**
     * @var string
     */
    protected $fileName = 'error.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::ERROR;
}
