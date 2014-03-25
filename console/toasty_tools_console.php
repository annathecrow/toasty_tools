<?php 
include_once("vendor/autoload.php");

function testRes($val = true) {
	return !$val;

}

//addcount
function addtocount($item, &$counter) {
	if (array_key_exists($item, $counter)) {
		$counter[$item]++;
	} else {
		$counter[$item] = 1;
	}
}


//printpair
function printpair($item,$count,$f) {
	fwrite($f,"$item, $count\n");
}

//printall
function printall($typecount, $tagcount, $datecount, $hourcount, $threshold, $outfile) {
	$f = fopen($outfile,"w");
	
	fwrite($f,"******* TYPES:\n");
	arsort($typecount,SORT_NUMERIC);
	foreach ($typecount as $type=>$amount) {
		printpair($type,$amount,$f);
	}
	
	fwrite($f,"******* DATES:\n");
	ksort($datecount);
	foreach ($datecount as $date=>$amount) {
		printpair($date,$amount,$f);
	}	
	
	fwrite($f,"******* HOURS:\n");
	ksort($hourcount);
	foreach ($hourcount as $hour=>$amount) {
		printpair($hour,$amount,$f);
	}
	fwrite($f,"******* TAGS:\n");
	arsort($tagcount,SORT_NUMERIC);
	array_shift($tagcount);	
	foreach ($tagcount as $tag=>$amount) {
		if ($amount>= $threshold) {
			printpair($tag,$amount,$f);
		}
	}
	//
	
}

//trytoprint

function trytoprint($r, $name, $f) {
	try {
		//"\n".print($r->$name)."\n";
		$value = $r->$name;
		if (gettype($value) ==='array') {
			$value = implode(", ", $value);
		}
		//echo $value;
		fwrite($f,(string)$value);
	}
	catch (Exception $e) {
		echo "couldn't write $name";
	}
	fwrite($f,", ");
}

//printpost
function printpost($postdata,$outfile) {
	$r = $postdata;
	
	try {
		$f = fopen($outfile, "a+");
	}
	catch (Exception $e) {
		echo "couldn't open outfile";
        return null;
	}
	trytoprint($r, 'id', $f);
    trytoprint($r, 'blog_name', $f);
    trytoprint($r, 'post_url', $f);
    trytoprint($r, 'timestamp', $f);
    trytoprint($r, 'date', $f);
    trytoprint($r, 'note_count', $f);  
    trytoprint($r, 'type', $f);
    trytoprint($r, 'slug', $f);
    trytoprint($r, 'tags', $f);
    fwrite($f,"\n");
    fclose($f);
}


/* main function */

$cmd = new Commando\Command();
$cmd->option()
	->require()
	->describedAs('tag')
->referToAs('tag');
$cmd->option()
	->require()
	->must(function($value){
		if (is_numeric($value)) {
			return true;
		} else {
			return false;
		}
	})
	->describedAs('number of posts to fetch')
	->referToAs('number of posts');
$cmd->option('f')
	->aka('frequency')
	->must(function($value){
		if (is_numeric($value)) {
			return true;
		} else {
			return false;
		}
	})
	->describedAs('frequency threshold of listed tags');
$cmd->option('m')
	->aka('multi')
	->boolean()
	->describedAs('make sure multiple posts by single user are fetched (slower)');
$tagarg = $cmd[0];
$numposts = $cmd[1];
$threshold = $cmd['frequency'];
if($cmd['m']) {
	define("BATCH",3);
} else {
	define("BATCH",20);
}
try {
	$folder = "output";
	if(!file_exists($folder)) {
		mkdir($folder,0755,true);
	} elseif (file_exists($folder)&&!is_dir($folder)){
		$folder = $folder."_1";
		mkdir($folder,0755,true);
	};
	$outfile = $folder."/".$tagarg."_stats.csv";
	$postfile = $folder."/".$tagarg."_posts.csv";
	$file = fopen($postfile,"w");
	fwrite($file,"id, blog, url, timestamp, date, notes, type, slug, tags\n");
	fclose($file);	
    }
    catch (Exception $e) {
    	exit("error: couldn't write file\n");    	
    }
    
	try {
		$auth = file(".auth");
		$key = trim($auth[0]);
		$secret = trim($auth[1]);		
	} catch (Exception $e) {
		exit("error: unable to get key and secret from the .auth file");
	}
$client = new Tumblr\API\Client(
	$key, //consumer key
	$secret //consumer secret
);


$timestamps = array();
$result = $client->getTaggedPosts($tagarg,array("limit"=>BATCH));

$earliest = $result[0]->timestamp;
$typecount = array();
$tagcount = array();
$datecount = array();
$hourcount = array();
	
while (count($timestamps) <= $numposts) {
	echo count($timestamps)." -- ";
	try {
		$num = 0;
		
		foreach ($result as $r) {
			$num++;
			
			//timestamps
			
			try {
				$ts = $r->timestamp;
				$timestamps[]=$ts;
				$earliest = ($earliest>$ts)?$ts:$earliest;
				$date = $r->date;
				$d = substr($date,0,10);
				$hour = substr($date,11,2);
				addtocount($d, $datecount);
				addtocount($hour, $hourcount);			
			}
			catch (Exception $e) {
				echo "failed to handle timestamp/date/hour count";
			}
			
			//types and tags
			try {
				$ty = $r->type;
				//this doesn't get written into
				addtocount($ty, $typecount);				
			}
			catch (Exception $e) {
				echo "failed to handle type count";
			}
			
			try {
				$ty = $r->tags;
				foreach ($ty as $tag) {
					$tag = strtolower($tag);
					addtocount($tag,$tagcount);					
				}				
			}
			catch (Exception $e) {
				echo "failed to handle type count";
			}
							
			//if postfile then print post
			
			if (file_exists($postfile)) {
				printpost($r, $postfile);
			}
			
			
		}
		
		 
	}
	catch (Exception $e) {
		echo "bad result $num";	
	}
	echo "posts fetched: ".count($result)."\n";
	printall($typecount, $tagcount, $datecount, $hourcount, $threshold, $outfile);	
	$result = $client->getTaggedPosts($tagarg,array("before"=>$earliest,"limit"=>BATCH));
	if (!$result) {
		break;
	}
}
?>
