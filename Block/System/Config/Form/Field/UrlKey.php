<?php
namespace Zero1\Gateway\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field as MagentoField;

class UrlKey extends MagentoField
{
    /**
     * Retrieve element HTML markup
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $element->getElementHtml().

        '<script>
            function regenerateEndpoint(){
                var characters = [
                    \'a\', \'b\', \'c\', \'d\', \'e\', \'f\', \'g\', \'h\', \'i\', \'j\', \'k\',
                    \'l\', \'m\', \'n\', \'o\', \'p\', \'q\', \'r\', \'s\', \'t\', \'u\', \'v\',
                    \'w\', \'x\', \'y\', \'z\', \'1\', \'2\', \'3\', \'4\', \'5\', \'6\', \'7\',
                    \'8\', \'9\', \'0\'
                ];
                var endpointLength = 255;

                var newEndpoint = \'\';
                while(newEndpoint.length < endpointLength){
                    newEndpoint += characters[
                        Math.floor((Math.random() * 100) / (100 / characters.length))
                    ];
                }

                jQuery(\'#'.$element->getHtmlId().'\').val(newEndpoint);

            }
        </script>'.

        $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData([
                'label' => 'Regenerate Endpoint',
                'onclick' => 'regenerateEndpoint();',
                'class' => '',
                'type' => 'button',
                //'id' => '',
            ]
        )->toHtml();
    }
}