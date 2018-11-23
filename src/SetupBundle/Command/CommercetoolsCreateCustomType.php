<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Command;


use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Type\FieldDefinition;
use Commercetools\Core\Model\Type\FieldDefinitionCollection;
use Commercetools\Core\Model\Type\StringType;
use Commercetools\Core\Model\Type\TypeDraft;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
        $customTypeKey =  $helper->ask($input, $output, $question);

        $question = new Question('Please enter the name of the CustomType: ');
        $customTypeName =  $helper->ask($input, $output, $question);

        $question = new Question('Please enter the two letter code of the language of the name you just entered: ');
        $customTypeNameLanguage =  $helper->ask($input, $output, $question);

        $question = new Question('Please enter the ids of the resources that this custom type can be applied at (comma ' .
            'separated for multiple) [supported types: line-item, shopping-list, review, channel, discount-code, product-price, ' .
            'shopping-list-text-line-item, customer-group, order-edit, custom-line-item, cart-discount, payment, ' .
            'payment-interface-interaction, order, customer, category, asset, inventory-entry]: ');
        $customTypeResourceIds =  $helper->ask($input, $output, $question);

        $fieldDefinitionCollection = FieldDefinitionCollection::of();

        $resourceIds = array_map('trim', explode(',', $customTypeResourceIds));
        $name = LocalizedString::ofLangAndText($customTypeNameLanguage, $customTypeName);

        do {
            // only support StringType for the moment
            $question = new Question('Please enter the name of a field: ');
            $fieldName =  $helper->ask($input, $output, $question);

            $question = new Question('Please enter the label of a field: ');
            $fieldLabel =  $helper->ask($input, $output, $question);

            $question = new Question('Please enter the language of the label of a field: ');
            $fieldLabelLanguage =  $helper->ask($input, $output, $question);

            $fieldDefinitionCollection->add(
                FieldDefinition::of()
                    ->setType(StringType::of())
                    ->setName($fieldName)
                    ->setLabel(LocalizedString::ofLangAndText($fieldLabelLanguage, $fieldLabel))
                    ->setRequired(true)
            );
            $continue = new ConfirmationQuestion('Add more fields? ', false);
        } while ($helper->ask($input, $output, $continue));

        $typeDraft = TypeDraft::ofKeyNameDescriptionAndResourceTypes($customTypeKey, $name, $name, $resourceIds);
        $typeDraft->setFieldDefinitions($fieldDefinitionCollection);

        $response = $this->repository->createCustomType($typeDraft);

        $output->writeln('API response: '.$response->getId());
    }
}
