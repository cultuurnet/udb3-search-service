<?php

namespace CultuurNet\UDB3\SearchService\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class TermTaxonomyToFacetMappingsCommand extends Command
{
    /**
     * XPATH definitions to find the relevant terms.
     *
     * In the case of the types, we only want to find the types that have a
     * parent. The parents themselves we're not interested in.
     *
     * Note that we have to use a wildcard * and the name() function to find
     * the term nodes, because the XML namespace messes with xpath.
     */
    const TYPES_XPATH = "//*[name()='term'][@domain='eventtype'][@parentid]";
    const THEMES_XPATH = "//*[name()='term'][@domain='theme']";
    const FACILITIES_XPATH = "//*[name()='term'][@domain='facility']";
    
    public function configure()
    {
        $this
            ->setName('facet-mapping:generate-from-taxonomy-terms')
            ->setDescription('Generates types, themes, and facilities facet mapping from XML taxonomy terms.')
            ->addArgument(
                'xmlFileUrl',
                InputArgument::OPTIONAL,
                'The taxonomy terms XML file url.',
                'http://taxonomy.uitdatabank.be/api/term'
            );
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);
        
        $xmlFileUrl = $input->getArgument('xmlFileUrl');
        
        $xmlString = @file_get_contents($xmlFileUrl);
        
        if (!$xmlString) {
            $logger->error("Could not load taxonomy terms XML from $xmlFileUrl");
            return 1;
        }
        
        $xml = new \SimpleXmlElement($xmlString);
        
        $this->generateYmlMapping('facet_mapping_types', $xml, self::TYPES_XPATH);
        $this->generateYmlMapping('facet_mapping_themes', $xml, self::THEMES_XPATH);
        $this->generateYmlMapping('facet_mapping_facilities', $xml, self::FACILITIES_XPATH);
        return 0;
    }
    
    /**
     * @param string $mappingName
     * @param \SimpleXmlElement $xml
     * @param string $xpath
     */
    private function generateYmlMapping(
        $mappingName,
        \SimpleXmlElement $xml,
        $xpath
    ) {
        $nodes = $xml->xpath($xpath);
        $mapping = [$mappingName => $this->simpleXmlNodesToFacetMapping($nodes)];
        $yml = Yaml::dump($mapping, 4, 2);
        file_put_contents(__DIR__ . "/../../{$mappingName}.yml", $yml);
    }
    
    /**
     * @param \SimpleXMLElement[] $simpleXmlNodes
     * @return array
     */
    private function simpleXmlNodesToFacetMapping(array $simpleXmlNodes)
    {
        $mapping = [];
        
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
            
            $mapping[$id] = ['name' => $name];
        }
        
        return $mapping;
    }
}
