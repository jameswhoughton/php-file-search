<?php

class Search {

	private $files;
	private $filters;
	private $count;
	private $search_string;
	private $search_string_array;
	private $search_string_length;
	private $search_table;


	public function __construct() {
		$this->files = array();
		$this->filters =  array();
		$this->count = 0;
	}

	public function setSearch($string) {
		$this->search_string = $string;
		$this->search_string_array = str_split($string);
		$this->search_string_length = strlen($string);
		$this->search_table = $this->generateSearchTable();
	}

	public function getCount() {
		return $this->count;
	}

	public function getFiles() {
		return $this->files;
	}

	public function setFilters($filters) {
		$this->filters = $filters;
	}

	public function grepSearch($dir) {
	    $items = scandir($dir);
		$n_files = sizeof($items);

	    foreach($items as $item) {
	        if($item !== '.' && $item !== '..' && $item !== '.DS_Store') {
	            if(is_file($dir .'/'. $item) && $this->filterFile($dir .'/'. $item)) {
					if($this->inFile($dir .'/'. $item)) {
						$this->files[] = array(
							'file' => $dir .'/'. $item,
							'result' => $this->searchFile($dir .'/'. $item)
						);
					}
	            } else if(is_dir($dir .'/'. $item)) {
					$this->grepSearch($dir .'/'. $item);
	            }
	        }
	    }
	}

	private function inFile($file) {
		$f = fopen($file, 'r');

		$pos = $this->search_string_length;
		$i = $this->search_string_length - 1;
		$c = 0;
		$j = 0;
		$n = $i;
		$l = '';

		while(!feof($f)) {
			fseek($f, $pos, SEEK_SET);
			$c = fgetc($f);
			if(in_array($c, $this->search_string_array)) {
				if($c === $this->search_string_array[$n]) {
					$l .= $c;
					$pos--;
					$j++;
					$n--;
				} else {
					$i = $this->search_table[$c];
					$l = '';
					$pos += $this->search_table[$c] + $j;
					$n = $this->search_string_length - 1;
					$j = 0;
				}
			} else {
				$pos += ($this->search_string_length + $j);
				$i = sizeof($this->search_string_array);
				$l = '';
				$j = 0;
				$n = $this->search_string_length - 1;
			}
			if(strlen($l) === $this->search_string_length) {
				return true;
			}
		}
		return false;
	}

	private function searchFile($file) {
		$result = array();
		$n = 1;

		$f = fopen($file, 'r');

		while($line = fgets($f)) {
			if(strpos($line, $this->search_string) !== false) {
				$this->count += 1;
				$result[] = array(
					'line_number' => $n,
					'line' => htmlspecialchars($line)
				);
			}
			$n++;
		}

		return $result;
	}

	private function generateSearchTable() {
		$out = array();
		$n = $max = $this->search_string_length - 1;
		for($i = 0; $i < $max; $i++) {
			$out[$this->search_string_array[$i]] = $n;
			$n--;
		}
		$out[$this->search_string_array[$i]] = $this->search_string_length;
		return $out;
	}

	private function filterFile($file) {
		/* Returns True if file passes filters */
		if(empty($this->filters)) {
			return true;
		} else {
			if($this->filters['binary']) {
				//TODO
			}

			if($this->filters['extension'] && in_array(pathinfo($file, PATHINFO_EXTENSION),$this->filters['extension'])) {
				return false;
			}

			if($this->filters['name'] && preg_match('/'. $this->filters['name'] .'/', $file)) {
				return false;
			}
			return true;
		}
	}
}
?>
