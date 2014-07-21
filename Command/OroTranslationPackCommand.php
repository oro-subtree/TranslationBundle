<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Yaml\Parser;

use Oro\Component\Log\Logger\OutputLogger;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\TranslationBundle\Provider\AbstractAPIAdapter;
use Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackDumper;

class OroTranslationPackCommand extends ContainerAwareCommand
{
    /** @var string */
    protected $path;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:translation:pack')
            ->setDescription('Dump translation messages and optionally upload them to third-party service')
            ->setDefinition(
                array(
                    new InputArgument('project', InputArgument::REQUIRED, 'The project [e.g Oro, OroCRM etc]'),
                    new InputArgument(
                        'locale',
                        InputArgument::OPTIONAL,
                        'The locale for creating language pack [en by default]',
                        'en'
                    ),
                    new InputArgument(
                        'adapter',
                        InputArgument::OPTIONAL,
                        'Uploader adapter, representing third-party service API, config value will be used if empty'
                    ),
                    new InputOption(
                        'project-id',
                        'i',
                        InputOption::VALUE_REQUIRED,
                        'API project ID'
                    ),
                    new InputOption(
                        'api-key',
                        'k',
                        InputOption::VALUE_REQUIRED,
                        'API key'
                    ),
                    new InputOption(
                        'upload-mode',
                        'm',
                        InputOption::VALUE_OPTIONAL,
                        'Uploader mode: add or update',
                        'add'
                    ),
                    new InputOption(
                        'output-format',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Override the default output format',
                        'yml'
                    ),
                    new InputOption(
                        'path',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Dump destination (or upload source), relative to %kernel.root_dir%',
                        '/Resources/language-pack/'
                    ),
                    new InputOption(
                        'dump',
                        null,
                        InputOption::VALUE_NONE,
                        'Create language pack for uploading to translation service'
                    ),
                    new InputOption(
                        'upload',
                        null,
                        InputOption::VALUE_NONE,
                        'Upload language pack to translation service'
                    ),
                    new InputOption(
                        'download',
                        null,
                        InputOption::VALUE_NONE,
                        'Download all language packs from project at translation service'
                    ),
                )
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command extract translation files for each bundle in
specified vendor namespace(project) and creates language pack that's placed at
%kernel.root_dir%/Resources/language-pack

    <info>php %command.full_name% --dump OroCRM</info>
    <info>php %command.full_name% --upload OroCRM</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        // check presence of action
        $modeOption = false;
        foreach (['dump', 'upload', 'download'] as $option) {
            $modeOption = $modeOption || $input->getOption($option) === true;
        }

        if (!$modeOption) {
            $output->writeln('<info>You must choose action: e.g --dump, --upload or --download</info>');
            return 1;
        }

        $this->path = $this->getContainer()->getParameter('kernel.root_dir')
            . str_replace('//', '/', $input->getOption('path') . '/');

        if ($input->getOption('dump') === true) {
            $this->dump($input, $output);
        }

        if ($input->getOption('upload') === true) {
            $this->upload($input, $output);
        }

        if ($input->getOption('download') === true) {
            $this->download($input, $output);
        }

        return 0;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function upload(InputInterface $input, OutputInterface $output)
    {
        $projectName            = $input->getArgument('project');
        $languagePackPath       = $this->getLangPackDir($projectName);
        $translationService     = $this->getTranslationService($input, $output);
        $mode                   = $input->getOption('upload-mode');

        if ($mode == 'update') {
            $translationService->update($languagePackPath);
        } else {
            $translationService->upload($languagePackPath);
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function download(InputInterface $input, OutputInterface $output)
    {
        $projectName = $input->getArgument('project');
        $locale      = $input->getArgument('locale');

        $languagePackPath = rtrim(
            $this->getLangPackDir($projectName),
            DIRECTORY_SEPARATOR
        );

        $result = $this
            ->getTranslationService($input, $output)
            ->download($languagePackPath, [$projectName], $locale);

        $output->writeln(sprintf("Download %s", $result ? 'successful' : 'failed'));
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return TranslationServiceProvider
     */
    protected function getTranslationService(InputInterface $input, OutputInterface $output)
    {
        $service = $this->getContainer()->get('oro_translation.service_provider');
        $service->setLogger(new OutputLogger($output));

        // set non default adapter if comes from input
        if ($adapter = $this->getAdapterFromInput($input)) {
            $service->setAdapter($adapter);
        }

        /*
         * Set project id and api key to adapter anyway if its provided
         */
        $projectId = $input->getOption('project-id');
        if (null !== $projectId) {
            $service->getAdapter()->setProjectId($projectId);
        }
        $apiKey = $input->getOption('api-key');
        if (null !== $apiKey) {
            $service->getAdapter()->setApiKey($apiKey);
        }

        return $service;
    }

    /**
     * @param InputInterface $input
     *
     * @throws \RuntimeException
     * @return AbstractAPIAdapter
     */
    protected function getAdapterFromInput(InputInterface $input)
    {
        $adapterOption = $input->getArgument('adapter');
        if (null === $adapterOption) {
            return false;
        }

        $serviceId = sprintf('oro_translation.uploader.%s_adapter', $adapterOption);
        if (!$this->getContainer()->has($serviceId)) {
            throw new \RuntimeException('Invalid adapter name given');
        }
        return $this->getContainer()->get($serviceId);
    }

    /**
     * Performs dump operation
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function dump(InputInterface $input, OutputInterface $output)
    {
        $projectNamespace = $input->getArgument('project');

        $output->writeln(sprintf('Dumping language pack for <info>%s</info>' . PHP_EOL, $projectNamespace));

        $container = $this->getContainer();
        $dumper = new TranslationPackDumper(
            $container->get('translation.writer'),
            $container->get('translation.extractor'),
            $container->get('translation.loader'),
            new Filesystem(),
            $container->get('kernel')->getBundles()
        );
        $dumper->setLogger(new OutputLogger($output));

        $languagePackPath = $this->getLangPackDir($projectNamespace);
        $dumper->dump(
            $languagePackPath,
            $projectNamespace,
            $input->getOption('output-format'),
            $input->getArgument('locale')
        );

        return true;
    }

    /**
     * Return lang pack location
     *
     * @param string      $projectNamespace
     * @param null|string $bundleName
     *
     * @return string
     */
    protected function getLangPackDir($projectNamespace, $bundleName = null)
    {
        $path = $this->path . $projectNamespace . DIRECTORY_SEPARATOR;

        if (!is_null($bundleName)) {
            $path .= $bundleName . DIRECTORY_SEPARATOR . 'translations';
        }

        return $path;
    }

    /**
     * Check yaml files in translation pack and display files and keywords that need translation.
     *
     * @param string $languagePackPath
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function checkFiles($languagePackPath, OutputInterface $output)
    {
        $needTranslate  = [];
        $result         = true;
        $finder         = Finder::create()->files()->name('*.yml')->in($languagePackPath);
        $yaml           = new Parser();

        foreach ($finder->files() as $file) {
            $value = $yaml->parse(file_get_contents((string)$file));
            array_walk(
                $value,
                function (&$value, $key) {
                    // key equal to value and key is dotted string, e.g. test.key.param
                    $wrongItemFlag = $value == $key && preg_match('#[^\s\.]\.[^\s\.]#', $key);

                    // semicolon exists in key and not encoded in value, should be
                    $semicolonDetected = (false !== strpos($key, ':')) && (false !== strpos($value, ':'));

                    if ($wrongItemFlag || $semicolonDetected) {
                        $value = '- ' . $value;
                    } else {
                        $value = false;
                    }
                }
            );
            $tempArr = array_filter($value);
            if (count($tempArr) > 0) {
                $needTranslate[(string)$file] = $tempArr;
                $result = false;
            }
        }

        foreach ($needTranslate as $key => $value) {
            $output->writeln(sprintf('<comment>Fix translation strings in %s</comment>', $key));
            $output->writeln($value);
        }
        return $result;
    }
}
