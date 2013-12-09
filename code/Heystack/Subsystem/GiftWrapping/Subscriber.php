<?php
/**
 * This file is part of the Ecommerce-GiftWrapping package
 *
 * @package Ecommerce-GiftWrapping
 */

/**
 * Tax namespace
 */
namespace Heystack\Subsystem\GiftWrapping;

use Heystack\Subsystem\Core\Storage\Backends\SilverStripeOrm\Backend;
use Heystack\Subsystem\Ecommerce\Currency\Events as CurrencyEvents;
use Heystack\Subsystem\Ecommerce\Transaction\Events as TransactionEvents;
use Heystack\Subsystem\GiftWrapping\Interfaces\GiftWrappingHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles both subscribing to events and acting on those events needed for TaxHandler to work properly
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-GiftWrapping
 * @see Symfony\Component\EventDispatcher
 */
class Subscriber implements EventSubscriberInterface
{
    /**
     * Holds the Event Dispatcher Service
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventService;

    /**
     * Holds the GiftWrapping Handler
     * @var \Heystack\Subsystem\GiftWrapping\Interfaces\GiftWrappingHandlerInterface
     */
    protected $giftWrappingHandler;

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventService
     * @param \Heystack\Subsystem\GiftWrapping\Interfaces\GiftWrappingHandlerInterface $giftWrappingHandler
     */
    public function __construct(EventDispatcherInterface $eventService, GiftWrappingHandlerInterface $giftWrappingHandler)
    {
        $this->eventService = $eventService;
        $this->giftWrappingHandler = $giftWrappingHandler;
    }

    /**
     * Returns an array of events to subscribe to and the methods to call when those events are fired
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            CurrencyEvents::CHANGED        => array('onUpdateTotal', 0),
            Events::TOTAL_UPDATED          => array('onTotalUpdated', 0)
        );
    }

    /**
     * Called to update the GiftWrappingHandler's total
     */
    public function onUpdateTotal()
    {
        $this->giftWrappingHandler->updateTotal();
    }

    /**
     * Called after the GiftWrappingHandler's total is updated.
     * Tells the transaction to update its total.
     */
    public function onTotalUpdated()
    {
        $this->eventService->dispatch(TransactionEvents::UPDATE);
    }


}
