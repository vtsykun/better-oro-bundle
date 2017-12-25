<?php

namespace Okvpn\Bundle\BetterOroBundle\Command;

use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Util\ArrayConverter;
use Symfony\Component\Yaml\Yaml;

class DumpYmlEntityTranslationsCommand extends ContainerAwareCommand
{
    const NAME = 'okvpn:entity-translations:dump';
    const INLINE_LEVEL = 10;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Dump translations for entity')
            ->addArgument(
                'entity',
                InputArgument::REQUIRED,
                'Entity class or bundle name whose translations should to be dumped, '
                . 'like "Oro/Bundle/TaskBundle/Entity/Task" or CuanticCampaignEmailBundle'
            )
            ->addOption(
                'locale',
                null,
                InputOption::VALUE_OPTIONAL,
                'Locale whose translations should to be dumped',
                Translator::DEFAULT_LOCALE
            )
            ->addOption(
                'skip-translated',
                null,
                InputOption::VALUE_NONE,
                'Skip translated label'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityClass = $input->getArgument('entity');
        $entityProvider = $this->getContainer()->get('oro_entity.entity_provider');
        $kernel = $this->getContainer()->get('kernel');
        $namespace = null;
        try {
            $bundle = $kernel->getBundle($entityClass);
            $namespace = $bundle->getNamespace();
        } catch (\Exception $e) {
        }

        if ($namespace !== null) {
            $entities = $entityProvider->getEntities(false, false, false);
            $entities = array_filter(
                $entities,
                function (array $config) use ($namespace) {
                    return strpos($config['name'], $namespace) === 0;
                }
            );

            $entities = array_column($entities, 'name');
        } else {
            $entities = [$entityClass];
        }

        foreach ($entities as $entity) {
            $this->processEntity($input, $output, $entity);
        }
    }


    protected function processEntity(InputInterface $input, OutputInterface $output, $entityClass)
    {
        $fieldsProvider = $this->getContainer()->get('oro_entity.entity_field_provider');
        $entity = $this->getContainer()->get('oro_entity.entity_provider')->getEntity($entityClass, false);
        $translationKeys = [$entity['label'], $entity['plural_label']];

        $translationKeys = array_merge(
            $translationKeys,
            array_map(
                function ($field) {
                    return $field['label'];
                },
                $fieldsProvider->getFields($entityClass, true, true, false, false, true, false)
            )
        );

        $translations = $this->processKeys(
            $this->getContainer()->get('translator.default'),
            $translationKeys,
            $input->getOption('locale'),
            $input->getOption('skip-translated')
        );

        $output->write(Yaml::dump(ArrayConverter::expandToTree($translations), self::INLINE_LEVEL));
    }

    protected function processKeys(Translator $translator, array $keys, $locale, $skipTranslated)
    {
        $translations = [];
        foreach ($keys as $key) {
            if ($translator->hasTrans($key, null, $locale)) {
                $translation = $translator->trans($key, [], null, $locale);
            } elseif ($translator->hasTrans($key, null, Translator::DEFAULT_LOCALE)) {
                $translation = $translator->trans($key, [], null, Translator::DEFAULT_LOCALE);
            } else {
                $translation = '';
            }

            if ($skipTranslated && $translation !== '') {
                continue;
            }

            $translations[$key] = $translation;
        }

        return $translations;
    }
}
