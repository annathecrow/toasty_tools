<?php

include_once("../get_tagstats.php");

class getTagsTest extends PHPUnit_Framework_TestCase
{
	public function testTest() {
		$newVal = testRes(false);
		$this->assertTrue($newVal);
	}
	
	public function test_addtocount() {
		$haystack = array(
			'long straw' => 4,
			'short straw' => 10,
			'fat straw' => 2
		);
		$found = 'fat straw';
		addtocount($found, &$haystack); //you have to pass as reference!
		$this->assertEquals(3,$haystack['fat straw']);
		$found = 'needle';
		addtocount($found, &$haystack);
		$this->assertEquals(1,$haystack['needle']);	
	}
	
	public function test_printpair() {
		$item = "fat straw";
		$haystack = array(
			'long straw' => 4,
			'short straw' => 10,
			'fat straw' => 2
		);
		$count = $haystack[$item];
		$file = fopen("file.txt","a+");
		if ($file==false) {
			throw new Exception("the file doesn't exist");
		} else {
			printpair($item,$count,$file);
			$filename = escapeshellarg("file.txt"); // for security
			$line = `tail -n 1 $filename`; //backticks = shell.
			$this->assertEquals($line,"$item, $count\n"); 
		}				
		fclose($file);
	}
}

?>