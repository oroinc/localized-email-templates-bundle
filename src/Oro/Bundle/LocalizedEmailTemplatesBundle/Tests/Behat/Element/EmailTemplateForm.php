<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

class EmailTemplateForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function fill(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            [$label] = $row;
            $fallbackLabel = $label . ' Fallback';
            $locator = $this->options['mapping'][$fallbackLabel] ?? null;
            if ($locator) {
                $selector = is_array($locator)
                    ? $locator
                    : ['type' => 'named', 'locator' => ['field', $locator]];

                $field = $this->find($selector['type'], $selector['locator']);
                if ($field && $field->isChecked()) {
                    $field->uncheck();
                    $field->blur();
                    $this->getDriver()->waitForAjax();
                }
            }
        }

        parent::fill($table);
    }
}
