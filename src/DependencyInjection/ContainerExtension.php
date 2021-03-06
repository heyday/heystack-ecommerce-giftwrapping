<?php
/**
 * This file is part of the Ecommerce-GiftWrapping package
 *
 * @package Ecommerce-GiftWrapping
 */

/**
 * GiftWrapping namespace
 */
namespace Heystack\GiftWrapping\DependencyInjection;

use Heystack\Core\Exception\ConfigurationException;
use Heystack\Core\Loader\DBClosureLoader;
use Heystack\GiftWrapping\Config\ContainerConfig;
use Heystack\GiftWrapping\Interfaces\GiftWrappingConfigInterface;
use Heystack\GiftWrapping\Interfaces\GiftWrappingHandlerInterface;
use Heystack\GiftWrapping\Services;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 *
 * @copyright  Heyday
 * @package Ecommerce-GiftWrapping
 *
 */
class ContainerExtension implements ExtensionInterface
{
    /**
     * Loads a services.yml file into a fresh container, ready to me merged
     * back into the main container
     * @param array $config
     * @param ContainerBuilder $container
     * @throws \Heystack\Core\Exception\ConfigurationException
     * @return void
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(ECOMMERCE_GIFT_WRAPPING_BASE_PATH . '/config')
        );

        $loader->load('services.yml');

        $validatedConfig = (new Processor())->processConfiguration(
            new ContainerConfig(),
            $config
        );

        if ( (isset($validatedConfig['config']) || isset($validatedConfig['config_db'])) && $container->hasDefinition(Services::GIFT_WRAPPING_HANDLER) ) {

            $priceConfig = [];

            if( isset($validatedConfig['config']) && count($validatedConfig['config']) ) {

                foreach ($validatedConfig['config'] as $currencyCodeConfig ) {

                    $priceConfig[$currencyCodeConfig['code']][GiftWrappingHandlerInterface::CONFIG_PRICE_KEY] = $currencyCodeConfig['price'];
                    $priceConfig[$currencyCodeConfig['code']][GiftWrappingHandlerInterface::CONFIG_MESSAGE_KEY] = $currencyCodeConfig['message'];

                }

            } else if ( isset($validatedConfig['config_db']) ) {
                $handler = function (GiftWrappingConfigInterface $record) use (&$priceConfig) {
                    $priceConfig[$record->getCurrencyCode()][GiftWrappingHandlerInterface::CONFIG_PRICE_KEY] = $record->getPrice();
                    $priceConfig[$record->getCurrencyCode()][GiftWrappingHandlerInterface::CONFIG_MESSAGE_KEY] = $record->getMessage();
                };

                (new DBClosureLoader($handler))->load([
                    $validatedConfig['config_db']['select'],
                    $validatedConfig['config_db']['from'],
                    $validatedConfig['config_db']['where']
                ]);
            }

            $container->getDefinition(Services::GIFT_WRAPPING_HANDLER)->addMethodCall('setConfig', [$priceConfig]);

        } else {
            throw new ConfigurationException('Please configure the gift wrapping subsystem on your /mysite/config/services.yml file');
        }
    }

    /**
     * Returns the namespace of the container extension
     * @return string
     */
    public function getNamespace()
    {
        return 'gift-wrapping';
    }

    /**
     * Returns Xsd Validation Base Path, which is not used, so false
     * @return boolean
     */
    public function getXsdValidationBasePath()
    {
        return false;
    }

    /**
     * Returns the container extensions alias
     * @return string
     */
    public function getAlias()
    {
        return 'gift-wrapping';
    }

}
