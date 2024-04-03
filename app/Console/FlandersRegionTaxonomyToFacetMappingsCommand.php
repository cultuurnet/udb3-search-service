<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Yaml;

final class FlandersRegionTaxonomyToFacetMappingsCommand extends Command
{
    /**
     * XPATH definitions to find the relevant terms.
     *
     * Note that we have to use a wildcard * and the name() function to find
     * the term nodes, because the XML namespace messes with xpath.
     */
    public const REGIONS_XPATH = "//*[name()='term'][@domain='flandersregion']";

    public function configure()
    {
        $this
            ->setName('facet-mapping:generate-regions-from-flandersregion-terms')
            ->setDescription('Generates region facet mapping from XML flandersregion terms.')
            ->addArgument(
                'outputFile',
                InputArgument::REQUIRED,
                'The yml file to write to.'
            )
            ->addArgument(
                'xmlFileUrl',
                InputArgument::OPTIONAL,
                'The taxonomy terms XML file url.',
                'http://taxonomy.uitdatabank.be/api/term'
            );
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        $outputFile = $input->getArgument('outputFile');

        if (file_exists($outputFile)) {
            /* @var QuestionHelper $questionHelper */
            $questionHelper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'Given output file already exists. Are you sure you want to update the existing mapping?',
                false
            );

            if (!$questionHelper->ask($input, $output, $question)) {
                return 0;
            }
        }

        $xmlFileUrl = $input->getArgument('xmlFileUrl');

        $xmlString = @file_get_contents($xmlFileUrl);

        if (!$xmlString) {
            $logger->error("Could not load taxonomy terms XML from $xmlFileUrl");
            return 1;
        }

        $xml = new \SimpleXmlElement($xmlString);

        $nodes = $xml->xpath(self::REGIONS_XPATH);
        $mapping = ['facet_mapping_regions' => $this->simpleXmlNodesToFacetMapping($nodes)];
        $yml = Yaml::dump($mapping, 10, 2);
        file_put_contents($outputFile, $yml);

        return 0;
    }

    /**
     * @param \SimpleXMLElement[] $simpleXmlNodes
     * @return array
     */
    private function simpleXmlNodesToFacetMapping(array $simpleXmlNodes)
    {
        $parentMapping = [];
        $facetMapping = [];

        // Map the region ids to their parent ids first. We need the complete
        // mapping before we can look up the full path of a given region id.
        foreach ($simpleXmlNodes as $simpleXmlNode) {
            $attributes = $simpleXmlNode->attributes();
            $id = (string) $attributes['id'];
            $parentId = (string) $attributes['parentid'];

            if (empty($parentId)) {
                $parentId = null;
            }

            $parentMapping[$id] = $parentId;
        }

        foreach ($simpleXmlNodes as $simpleXmlNode) {
            $attributes = $simpleXmlNode->attributes();

            $id = (string) $attributes['id'];

            $name = [
                'nl' => (string) $attributes['labelnl'],
                'fr' => (string) $attributes['labelfr'],
                'de' => (string) $attributes['labelde'],
                'en' => (string) $attributes['labelen'],
            ];

            $name = array_filter(
                $name,
                function ($translation) {
                    return !empty($translation);
                }
            );

            $parentIds = [];
            $lookupId = $id;
            while (!is_null($parentMapping[$lookupId])) {
                $parentId = $parentMapping[$lookupId];
                $parentIds[] = $parentId;
                $lookupId = $parentId;
            }
            $parentIds = array_reverse($parentIds);

            $appendTo = &$facetMapping;
            foreach ($parentIds as $parentId) {
                $appendTo = &$appendTo[$parentId]['children'];
            }

            $appendTo[$id] = ['name' => $name];
        }

        return $facetMapping;
    }
}
