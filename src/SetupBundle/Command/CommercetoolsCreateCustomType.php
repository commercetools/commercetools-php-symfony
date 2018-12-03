<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Command;


use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Type\FieldDefinition;
use Commercetools\Core\Model\Type\FieldDefinitionCollection;
use Commercetools\Core\Model\Type\TypeDraft;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class CommercetoolsCreateCustomType extends ContainerAwareCommand
{
    private $repository;

    public function __construct(SetupRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    protected function configure()
    {
        $this
            ->setName('commercetools:create-custom-type')
            ->setDescription('Apply the configuration of the conf file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new Question('Please enter the key of the CustomType: ');
        $customTypeKey = $helper->ask($input, $output, $question);

        $question = new Question('Please enter the name of the CustomType: ');
        $customTypeName = $helper->ask($input, $output, $question);

        $question = new Question('Please enter the two letter code of the language of the name you just entered [en]: ', 'en');
        $customTypeNameLanguage = $helper->ask($input, $output, $question);

        $question = new ChoiceQuestion('Please enter the ids of the resources that this custom type can be applied at: ',
            ['line-item', 'shopping-list', 'review', 'channel', 'discount-code', 'product-price',
            'shopping-list-text-line-item', 'customer-group', 'order-edit', 'custom-line-item', 'cart-discount', 'payment',
            'payment-interface-interaction', 'order', 'customer', 'category', 'asset', 'inventory-entry'],
            0
        );
        $question->setMultiselect(true);
        $customTypeResourceIds = $helper->ask($input, $output, $question);

        $fieldDefinitionCollection = FieldDefinitionCollection::of();
        $name = LocalizedString::ofLangAndText($customTypeNameLanguage, $customTypeName);

        do {
            // only support "simple" types for the moment
            $question = new ChoiceQuestion('Please selecte the type: ',
                ['BooleanType', 'StringType', 'LocalizedStringType', 'NumberType', 'MoneyType', 'DateType', 'TimeType', 'DateTimeType'],
                0
            );
            $fieldType = $helper->ask($input, $output, $question);
            $fieldTypeClass = 'Commercetools\\Core\\Model\\Type\\' . $fieldType;

            $question = new Question('Please enter the name of a field: ');
            $fieldName = $helper->ask($input, $output, $question);

            $question = new Question('Please enter the label of a field: ');
            $fieldLabel = $helper->ask($input, $output, $question);

            $question = new Question('Please enter the two letter code of the language of the label of the field [en]: ', 'en');
            $fieldLabelLanguage = $helper->ask($input, $output, $question);

            $question = new ChoiceQuestion('Is this field required [true]: ', ['true', 'false'], 0);
            $fieldRequired = $helper->ask($input, $output, $question);

            $fieldDefinitionCollection->add(
                FieldDefinition::of()
                    ->setType($fieldTypeClass::of())
                    ->setName($fieldName)
                    ->setLabel(LocalizedString::ofLangAndText($fieldLabelLanguage, $fieldLabel))
                    ->setRequired(($fieldRequired === 'true' ? true : false))
            );
            $continue = new ConfirmationQuestion('Add more fields? ', false);
        } while ($helper->ask($input, $output, $continue));

        $typeDraft = TypeDraft::ofKeyNameDescriptionAndResourceTypes($customTypeKey, $name, $name, $customTypeResourceIds);
        $typeDraft->setFieldDefinitions($fieldDefinitionCollection);

        $response = $this->repository->createCustomType($typeDraft);

        if (!is_null($response->getId())) {
            $output->writeln('Success! API response type Id: ' . $response->getId());
        } else {
            $output->writeln('Error! API response: ' . $response->getError());
        }
    }
}
