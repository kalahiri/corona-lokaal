<?php
/**
 * This file is written by Kalahiri in 2020-2021 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */

/**
 *	Instructie xpath queries:
 *	Zoek een tag (<h1>) in de gehele pagina: 			$elements = $xpath->query("//h1");
 *	Zoek een class in de gehele pagina: 				$elements = $xpath->query("//*[@class='agendablok']");
 *	Als er meerdere classes zijn voor een element: 			$elements = $xpath->query("//*[contains(@class, 'Test')]");
 *	Zoek een tag (<li>) ergens binnen een bepaalde eerdere query 	$childElements = $xpath->query("li", $element);
 *	Zoek een tag (<li>) ergens binnen een bepaalde class 		$elements = $xpath->query("//*[@class='agendablok']//li");
 *	
 *  Haal alle linkjes uit een bepaalde selectie         $elements = $xpath->query("//li[contains(@class, 'i_plan')]//a/@href"); 
 *
 *	
 *	ster betekent dat het een willekeurige tag kan zijn				$elements = $xpath->query("//*[@class='agendablok']");
 *	één slash betekent dat er een directe relaties is 				$elements = $xpath->query("/html/body/h1"); 
 *	twee slashes betekent dat er willekeurig tags tussen kunnen zitten 	$elements = $x_path->query("/html/body//div[@class='listing']/div[last()]");
 *	
 */


class DomParser {
    private $domDocument;
    private $xpath;
    const RETURN_NODE_LIST = 'return_node_list';
    const RETURN_FIRST_NODE = 'return_first_node';


    /**
     * Zet de HTMl pagina om in een Document Object Model om handig de elementen te kunnen selecteren
     */
    public function __construct( $webPage ) {
        $this->domDocument = new DOMDocument();	
        $this->domDocument->preserveWhiteSpace = false; // haal ook de witruimte weg als er elementen (nodes) verwijderd worden
        libxml_use_internal_errors(true);
        if ( ! $this->domDocument->loadHTML( $webPage ) ) {
            foreach ( libxml_get_errors() as $error ) {
                new LogException( "HTML is niet zuiver (dat hoeft niet erg te zijn).", __LINE__ );
            }
            libxml_clear_errors();
        }
        libxml_use_internal_errors(false);
        $this->xpath = new DomXPath(  $this->domDocument );
    }


    /**
     * Perform a xPath query on the domDocument
     * example: query( ".//div[@class='title-section']", $subNodes->item(0) );
     */
    public function query( $query, $context = NULL, $returnType = self::RETURN_FIRST_NODE ) {
        $nodeList = $this->xpath->query( $query, $context );
        if( $nodeList->length > 0 ) {
            if (  $returnType == self::RETURN_FIRST_NODE ) {
                return $nodeList->item(0);
            } else {
                return $nodeList;
            }
        }
        new LogException( 'nothing selected by query ' . $query );
        return false;
    }


    /**
     * Selects a node or nodes in the webpage selected by className
     * 
     * @var className { string } Name of the class to be selected 
     * @var element { html-tag } The tag-name of the specific HTML-element(s) to be selected 
     * @var context { node } The context to select within. Should be a single node within the DomDocument
     * @var returnType { flag } returns first node or a complete nodelist
     * 
     * @return { node } the selected node or nodelist
     */
    public function selectByClass( $className, $element = '*', $context = NULL, $returnType = self::RETURN_FIRST_NODE ) {
        $query = '//' . $element . '[contains(@class, "'. $className . '" )]';
        return $this->query( $query, $context, $returnType );
    }


    /**
     * Selects a node or nodelist in the webpage selected by a specific string of text.
     * Notice: The text search is case sensitive
     * 
     * @var text { string } the text to search for 
     * @var element { html-tag } The tag-name of the specific HTML-element(s) to be selected 
     * @var context { node } The context to select within. Should be a single node within the DomDocument
     * @var returnType { flag } returns first node or a complete nodelist
     * 
     * @return { node } the selected node or nodelist
     */
    public function selectByText( $text, $element = '*', $context = NULL, $returnType = self::RETURN_FIRST_NODE ) {
        $query = '//' . $element . '[text()[contains(., "'. $text . '")]]';
        return $this->query( $query, $context, $returnType );
    }


    /**
     * $context should be a single node within the DomDocument
     */
    public function selectById( $idName, $element = '*', $context = NULL, $returnType = self::RETURN_FIRST_NODE ) {
        $query = '//' . $element . '[@id="' . $idName . '"]';
        return $this->query( $query, $context );
    }


    /**
     * Selects a node or nodelist in the webpage selected by a specific HTML-element's tag name
     * 
     * @var tag { string } the tag to search for like 'p', 'div' or 'h1'
     * @var context { node } The context to select within. Should be a single node within the DomDocument
     * @var returnType { flag } returns first node or a complete nodelist
     * 
     * @return { node } the selected node or nodelist
     */
    public function selectByTag( $tag, $context = NULL, $returnType = self::RETURN_FIRST_NODE ) {
        $query = './/' . $tag;
        return $this->query( $query, $context, $returnType );
    }

    /**
     * Get the value of a node including all html tags.
     * As all html code is returned, beware of XXS on further processing of the string
     * 
     * @var node { node } the node to get all the html code from
     * 
     * @return { string } string containging all inner HTMl. Beware of XXS!
     */
    public function saveXML( $node ) {
        return $this->domDocument->saveXML( $node );
    }





    /**
     * Convert NodeList to array()
     *
     * @var nodelist { DOMNodeList } The list to convert
     */
    public function convertToArray( $nodeList ) {
        return iterator_to_array( $nodeList );
    }


    /**
     * Get the value or text of a single node.
     * 
     * @var node { DOMNodeElement } The node to get the value of.
     */
    public function toString( $node ) {
        if( isset( $node->nodeValue ) ) return trim( $node->nodeValue );
        if( isset( $node->textContent ) ) return trim( $node->textContent );
        if( isset( $node->item(0)->nodeValue ) ) {
            new LogException( 'You submitted a nodelist, not a single node. Use $nodelist->item(0)->toString()', __LINE__ );
            return false;
        }
        new LogException( "No property nodeValue or textContent found.", __LINE__ );
        return false;

    }


    /**
     * var_dump() for nodelists. If the $nodelist appears to be a single node, default var_dump() is used.
     * 
     * @var nodelist { DOMNodeList | DOMNodeElement } The list or element to dump
     */
    public function varDumpNodeList( $nodeList ) {
        if( $nodeList instanceof DOMNodeList ) {
            var_dump( $this->convertToArray( $nodeList ) );
        } else {
            var_dump( $nodeList );
        }
    }




}