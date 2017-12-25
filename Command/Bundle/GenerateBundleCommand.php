<?php

namespace Okvpn\Bundle\BetterOroBundle\Command\Bundle;

use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Sensio\Bundle\GeneratorBundle\Command\GenerateBundleCommand as BaseGenerateBundleCommand;


class GenerateBundleCommand extends BaseGenerateBundleCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('oro:generate:bundle');
    }

    /**
     * @see Command
     * {@inheritdoc}
     * @throws \InvalidArgumentException When namespace doesn't end with Bundle
     * @throws \RuntimeException         When bundle can't be executed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();

        $bundle = $this->createBundleObject($input);
        $questionHelper->writeSection($output, 'Bundle generation');

        /** @var BundleGenerator $generator */
        $generator = $this->getGenerator();

        $output->writeln(sprintf(
            '> Generating a sample bundle skeleton into <info>%s</info>',
            $this->makePathRelative($bundle->getTargetDirectory())
        ));
        $generator->generateBundle($bundle);

        $errors = array();

        // check that the namespace is already autoloaded
        $this->checkAutoloader($output, $bundle);

        if (!$bundle->shouldGenerateDependencyInjectionDirectory()) {
            // we need to import their services.yml manually!
            $this->updateConfiguration($output, $bundle);
        }

        $questionHelper->writeGeneratorSummary($output, $errors);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to the Symfony bundle generator!');


        $question = new ConfirmationQuestion($questionHelper->getQuestion(
            'Are you planning on sharing this bundle across multiple applications?',
            'yes'
        ), 'yes');
        $shared = $questionHelper->ask($input, $output, $question);
        $input->setOption('shared', $shared);

        /*
         * namespace option
         */
        $namespace = $input->getOption('namespace');
        $output->writeln(array(
            '',
            'Your application code must be written in <comment>bundles</comment>. This command helps',
            'you generate them easily.',
            '',
        ));

        $askForBundleName = true;
        if ($shared) {
            // a shared bundle, so it should probably have a vendor namespace
            $output->writeln(array(
                'Each bundle is hosted under a namespace (like <comment>Acme/BlogBundle</comment>).',
                'The namespace should begin with a "vendor" name like your company name, your',
                'project name, or your client name, followed by one or more optional category',
                'sub-namespaces, and it should end with the bundle name itself',
                '(which must have <comment>Bundle</comment> as a suffix).',
                '',
                'See http://symfony.com/doc/current/cookbook/bundles/best_practices.html#bundle-name for more',
                'details on bundle naming conventions.',
                '',
                'Use <comment>/</comment> instead of <comment>\\ </comment> for the namespace delimiter to avoid any problem.',
                '',
            ));

            $question = new Question($questionHelper->getQuestion(
                'Bundle namespace',
                $namespace
            ), $namespace);
            $question->setValidator(function ($answer) {
                return Validators::validateBundleNamespace($answer, true);
            });
            $namespace = $questionHelper->ask($input, $output, $question);
        } else {
            // a simple application bundle
            $output->writeln(array(
                'Give your bundle a descriptive name, like <comment>BlogBundle</comment>.',
            ));

            $question = new Question($questionHelper->getQuestion(
                'Bundle name',
                $namespace
            ), $namespace);
            $question->setValidator(function ($inputNamespace) {
                return Validators::validateBundleNamespace($inputNamespace, false);
            });
            $namespace = $questionHelper->ask($input, $output, $question);

            if (strpos($namespace, '\\') === false) {
                // this is a bundle name (FooBundle) not a namespace (Acme\FooBundle)
                // so this is the bundle name (and it is also the namespace)
                $input->setOption('bundle-name', $namespace);
                $askForBundleName = false;
            }
        }
        $input->setOption('namespace', $namespace);

        /*
         * bundle-name option
         */
        if ($askForBundleName) {
            $bundle = $input->getOption('bundle-name');
            // no bundle yet? Get a default from the namespace
            if (!$bundle) {
                $bundle = strtr($namespace, array('\\Bundle\\' => '', '\\' => ''));
            }

            $output->writeln(array(
                '',
                'In your code, a bundle is often referenced by its name. It can be the',
                'concatenation of all namespace parts but it\'s really up to you to come',
                'up with a unique name (a good practice is to start with the vendor name).',
                'Based on the namespace, we suggest <comment>'.$bundle.'</comment>.',
                '',
            ));
            $question = new Question($questionHelper->getQuestion(
                'Bundle name',
                $bundle
            ), $bundle);
            $question->setValidator(
                array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateBundleName')
            );
            $bundle = $questionHelper->ask($input, $output, $question);
            $input->setOption('bundle-name', $bundle);
        }

        /*
         * dir option
         */
        // defaults to src/ in the option
        $dir = $input->getOption('dir');
        $output->writeln(array(
            '',
            'Bundles are usually generated into the <info>src/</info> directory. Unless you\'re',
            'doing something custom, hit enter to keep this default!',
            '',
        ));

        $question = new Question($questionHelper->getQuestion(
            'Target Directory',
            $dir
        ), $dir);
        $dir = $questionHelper->ask($input, $output, $question);
        $input->setOption('dir', $dir);

        /*
         * format option
         */
        $format = $input->getOption('format');
        if (!$format) {
            $format = 'annotation';
        }
        $output->writeln(array(
            '',
            'What format do you want to use for your generated configuration?',
            '',
        ));

        $question = new Question($questionHelper->getQuestion(
            'Configuration format (annotation, yml, xml, php)',
            $format
        ), $format);
        $question->setValidator(function ($format) {
            return Validators::validateFormat($format);
        });
        $question->setAutocompleterValues(array('annotation', 'yml', 'xml', 'php'));
        $format = $questionHelper->ask($input, $output, $question);
        $input->setOption('format', $format);
    }

    protected function createGenerator()
    {
        return new BundleGenerator($this->getContainer()->get('filesystem'));
    }

    protected function getSkeletonDirs(BundleInterface $bundle = null)
    {
        $skeletonDirs = parent::getSkeletonDirs($bundle);
        $skeletonDirs[] = __DIR__.'/skeleton';

        return $skeletonDirs;
    }
}
