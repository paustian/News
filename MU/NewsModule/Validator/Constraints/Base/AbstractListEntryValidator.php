<?php
/**
 * News.
 *
 * @copyright Michael Ueberschaer (MU)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Michael Ueberschaer <info@homepages-mit-zikula.de>.
 * @link https://homepages-mit-zikula.de
 * @link http://zikula.org
 * @version Generated by ModuleStudio (https://modulestudio.de).
 */

namespace MU\NewsModule\Validator\Constraints\Base;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use MU\NewsModule\Helper\ListEntriesHelper;

/**
 * List entry validator.
 */
abstract class AbstractListEntryValidator extends ConstraintValidator
{
    use TranslatorTrait;

    /**
     * @var ListEntriesHelper
     */
    protected $listEntriesHelper;

    /**
     * ListEntryValidator constructor.
     *
     * @param TranslatorInterface $translator        Translator service instance
     * @param ListEntriesHelper   $listEntriesHelper ListEntriesHelper service instance
     */
    public function __construct(TranslatorInterface $translator, ListEntriesHelper $listEntriesHelper)
    {
        $this->setTranslator($translator);
        $this->listEntriesHelper = $listEntriesHelper;
    }

    /**
     * Sets the translator.
     *
     * @param TranslatorInterface $translator Translator service instance
     */
    public function setTranslator(/*TranslatorInterface */$translator)
    {
        $this->translator = $translator;
    }

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value) {
            return;
        }

        if ($constraint->propertyName == 'workflowState' && in_array($value, ['initial', 'deleted'])) {
            return;
    	}

        $listEntries = $this->listEntriesHelper->getEntries($constraint->entityName, $constraint->propertyName);
        $allowedValues = [];
        foreach ($listEntries as $entry) {
            $allowedValues[] = $entry['value'];
        }

        if (!$constraint->multiple) {
            // single-valued list
            if (!in_array($value, $allowedValues)) {
                $this->context->buildViolation(
                    $this->__f('The value "%value%" is not allowed for the "%property%" property.', [
                        '%value%' => $value,
                        '%property%' => $constraint->propertyName
                    ])
                )->addViolation();
            }

            return;
        }

        // multi-values list
        $selected = explode('###', $value);
        foreach ($selected as $singleValue) {
            if ($singleValue == '') {
                continue;
            }
            if (!in_array($singleValue, $allowedValues)) {
                $this->context->buildViolation(
                    $this->__f('The value "%value%" is not allowed for the "%property%" property.', [
                        '%value%' => $singleValue,
                        '%property%' => $constraint->propertyName
                    ])
                )->addViolation();
            }
        }

        $count = count($value);

        if (null !== $constraint->min && $count < $constraint->min) {
            $this->context->buildViolation(
                $this->__fn('You must select at least "%limit%" choice.', 'You must select at least "%limit%" choices.', $count, [
                    '%limit%' => $constraint->min
                ])
            )->addViolation();
        }
        if (null !== $constraint->max && $count > $constraint->max) {
            $this->context->buildViolation(
                $this->__fn('You must select at most "%limit%" choice.', 'You must select at most "%limit%" choices.', $count, [
                    '%limit%' => $constraint->max
                ])
            )->addViolation();
        }
    }
}
