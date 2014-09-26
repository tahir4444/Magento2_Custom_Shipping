<?php
/**
 * Created by Lilian Codreanu @Evozon.
 * User: Lilian.Codreanu@evozon.com
 * Date: 25.09.2014
 * Time: 16:15
 */

namespace Evz\Shipping\Model;


use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Sales\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

class Carrier extends AbstractCarrier implements CarrierInterface {

    /**
     * Code of the carrier
     * @var string
     */
    protected $_code='evz_shipping';

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Sales\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * Rate result data
     *
     * @var Result
     */
    protected $_result;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Magento\Framework\Logger\AdapterFactory $logAdapterFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Sales\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Magento\Framework\Logger\AdapterFactory $logAdapterFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Sales\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        array $data = array()
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logAdapterFactory, $data);
    }

    /**
     * Returns array of key-value pairs of all available methods
     * @return array
     */
    public function getAllowedMethods()
    {
        return array(
            'standard'  =>  'Standard delivery',
            'express'   =>  'Express delivery'
        );
    }

    /**
     * @param RateRequest $request
     * @return bool|Result|null
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $expressAvailable = true;
        $expressMaxWeight = $this->getConfigData('express_max_weight');
        $shippingTotalWeight = 0;

        $this->_result = $this->_rateResultFactory->create();

        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $shippingTotalWeight += $child->getWeight();
                        }
                    }
                } elseif ($item->getFreeShipping()) {
                    $shippingTotalWeight += $item->getWeight();
                }
            }
        }
        if ($shippingTotalWeight > $expressMaxWeight) {
            $expressAvailable = false;
        }

        if ($expressAvailable) {
            $this->_getExpressRate();
        }
        $this->_getStandardRate();

        return $this->getResult();
    }

    /**
     * Get result of request
     * @return Result
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * @return $this
     */
    protected function _getStandardRate()
    {
        $rate = $this->_rateMethodFactory->create();
        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethodTitle('Standard delivery');
        $rate->setPrice(1.23);
        $rate->setCost(0);
        $this->_result->append($rate);
        return $this;
    }

    /**
     * @return $this
     */
    protected function _getExpressRate()
    {
        $rate = $this->_rateMethodFactory->create();
        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethodTitle('Express delivery');
        $rate->setPrice(2.50);
        $rate->setCost(0);
        $this->_result->append($rate);
        return $this;
    }

} 