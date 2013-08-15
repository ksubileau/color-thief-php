<?php
/*
 * Color Thief PHP
 * 
 * Grabs the dominant color or a representative color palette from an image.
 * 
 * This class requires the GD library to be installed on the server.
 * 
 * It's a PHP port of the Color Thief Javascript library 
 * (http://github.com/lokesh/color-thief), using the MMCQ 
 * (modified median cut quantization) algorithm from 
 * the Leptonica library (http://www.leptonica.com/).
 * 
 * by Kevin Subileau - http://www.kevinsubileau.fr
 * Based on the work done by Lokesh Dhakar - http://www.lokeshdhakar.com
 * and Nick Rabinowitz
 *
 * License
 * -------
 * Creative Commons Attribution 2.5 License:
 * http://creativecommons.org/licenses/by/2.5/
 *
 * Thanks
 * ------
 * Lokesh Dhakar - For creating the original project.
 * Nick Rabinowitz - For creating quantize.js.
 *
 */


// Private constants
define('SIGBITS', 5);
define('RSHIFT', 8 - SIGBITS);
define('MAX_ITERATIONS', 1000);
define('FRACT_BY_POPULATIONS', 0.75);

// Simple priority queue
class PQueue {
	private $contents = array();
	private $sorted = false;
	private $comparator = null;
	
	function __construct($comparator) {
		$this->comparator = $comparator;
	}
	
	private function sort() {
		usort($this->contents, $this->comparator);
		$this->sorted = true;
	}
	
	public function push($object) {
		array_push($this->contents, $object);
		$this->sorted = false;
	}
	
	public function peek($index = null) {
		if (! $this->sorted)
			$this->sort();
		
		if (index === null)
			$index = $this->size() - 1;
		
		return $this->contents[$index];
	}
	
	public function pop() {
		if (! $this->sorted)
			$this->sort();
		
		return array_pop($this->contents);
	}
	
	public function size() {
		return count($this->contents);
	}
	
	public function map($function) {
		return array_map($function, $this->contents);
	}
	
	public function debug() {
		if (! $this->sorted)
			$this->sort();
		
		return $this->contents;
	}
}

class VBox {
	public $r1;
	public $r2;
	public $g1;
	public $g2;
	public $b1;
	public $b2;
	public $histo;
	
	private $_volume = false;
	private $_count;
	private $_count_set = false;
	private $_avg = false;
	
	function __construct($r1, $r2, $g1, $g2, $b1, $b2, $histo) {
		$this->r1 = $r1;
		$this->r2 = $r2;
		$this->g1 = $g1;
		$this->g2 = $g2;
		$this->b1 = $b1;
		$this->b2 = $b2;
		$this->histo = $histo;
	}
	
	public function volume($force = false) {
		if (! $this->_volume || $force) {
			$this->_volume = (($this->r2 - $this->r1 + 1) * ($this->g2 - $this->g1 + 1) * ($this->b2 - $this->b1 + 1));
		}
		return $this->_volume;
	}
	
	public function count($force = false) {
		if (! $this->_count_set || $force) {
			$npix = 0;
			for($i = $this->r1; $i <= $this->r2; $i ++) {
				for($j = $this->g1; $j <= $this->g2; $j ++) {
					for($k = $this->b1; $k <= $this->b2; $k ++) {
						$index = getColorIndex($i, $j, $k);
						if (isset($this->histo[$index]))
							$npix += $this->histo[$index];
					}
				}
			}
			$this->_count = $npix;
			$this->_count_set = true;
		}
		return $this->_count;
	}
	
	public function copy() {
		return new VBox($this->r1, $this->r2, $this->g1, $this->g2, $this->b1, $this->b2, $this->histo);
	}
	
	public function avg($force = false) {
		if (! $this->_avg || $force) {
			$ntot = 0;
			$mult = 1 << (8 - SIGBITS);
			$rsum = 0;
			$gsum = 0;
			$bsum = 0;
			
			for($i = $this->r1; $i <= $this->r2; $i ++) {
				for($j = $this->g1; $j <= $this->g2; $j ++) {
					for($k = $this->b1; $k <= $this->b2; $k ++) {
						$histoindex = getColorIndex($i, $j, $k);
						$hval = isset ($this->histo[$histoindex]) ? $this->histo[$histoindex] : 0;
						$ntot += $hval;
						$rsum += ($hval * ($i + 0.5) * $mult);
						$gsum += ($hval * ($j + 0.5) * $mult);
						$bsum += ($hval * ($k + 0.5) * $mult);
					}
				}
			}
			
			if ($ntot) {
				$this->_avg = array (
						~ ~ ($rsum / $ntot),
						~ ~ ($gsum / $ntot),
						~ ~ ($bsum / $ntot) 
				);
			} else {
				// echo 'empty box'."\n";
				$this->_avg = array (
						~ ~ ($mult * ($this->r1 + $this->r2 + 1) / 2),
						~ ~ ($mult * ($this->g1 + $this->g2 + 1) / 2),
						~ ~ ($mult * ($this->b1 + $this->b2 + 1) / 2) 
				);
			}
		}
		return $this->_avg;
	}
	
	public function contains(array $pixel) {
		$rval = $pixel[0] >> RSHIFT;
		$gval = $pixel[1] >> RSHIFT;
		$bval = $pixel[2] >> RSHIFT;
		
		return ($rval >= $this->r1 && $rval <= $this->r2 && $gval >= $this->g1 && $gval <= $this->g2 && $bval >= $this->b1 && $bval <= $this->b2);
	}
}

// Color map
class CMap {
	private $vboxes;
	
	function __construct() {
		$this->vboxes = new PQueue(function ($a, $b) {
			return naturalOrder($a['vbox']->count() * $a['vbox']->volume(), $b['vbox']->count() * $b['vbox']->volume());
		});
	}
	
	public function push($vbox) {
		$this->vboxes->push (array(
				'vbox' => $vbox,
				'color' => $vbox->avg() 
			));
	}
	
	public function palette() {
		return $this->vboxes->map(function ($vb) {
			return $vb['color'];
		});
	}
	
	public function size() {
		return count($this->vboxes);
	}
	
	public function map($color) {
		for($i = 0; $i < $this->vboxes->size(); $i ++) {
			$vbox = $this->vboxes->peek($i);
			if ($vbox['vbox']->contains($color)) {
				return $vbox['color'];
			}
		}
		return $this->nearest($color);
	}
	
	public function nearest($color) {
		for($i = 0; i < $this->vboxes->size(); $i ++) {
			$vbox = $this->vboxes->peek($i);
			$d2 = sqrt(pow($color[0] - $vbox['color'][0], 2) + pow($color[1] - $vbox['color'][1], 2) + pow($color[2] - $vbox['color'][2], 2));
			if (! isset($d1) || $d2 < $d1) {
				$d1 = $d2;
				$pColor = $vbox['color'];
			}
		}
		return pColor;
	}
	
	public function forcebw() {
            // XXX: won't work yet
            /*
             vboxes = this.vboxes;
            vboxes.sort(function(a,b) { return pv.naturalOrder(pv.sum(a.color), pv.sum(b.color) )});

            // force darkest color to black if everything < 5
            var lowest = vboxes[0].color;
            if (lowest[0] < 5 && lowest[1] < 5 && lowest[2] < 5)
                vboxes[0].color = [0,0,0];

            // force lightest color to white if everything > 251
            var idx = vboxes.length-1,
                highest = vboxes[idx].color;
            if (highest[0] > 251 && highest[1] > 251 && highest[2] > 251)
                vboxes[idx].color = [255,255,255];
                */
	}
}

function naturalOrder($a, $b) {
	return ($a < $b) ? - 1 : (($a > $b) ? 1 : 0);
}

// get reduced-space color index for a pixel
function getColorIndex($r, $g, $b, $sigbits = SIGBITS) {
	return ($r << (2 * $sigbits)) + ($g << $sigbits) + $b;
}

// histo (1-d array, giving the number of pixels in
// each quantized region of color space), or null on error
function getHisto($pixels) {
	$histosize = 1 << (3 * SIGBITS);
	$histo = array ();
	
	foreach($pixels as $rgb) {
		$rval = (($rgb >> 16) & 0xFF) >> RSHIFT;
		$gval = (($rgb >> 8) & 0xFF) >> RSHIFT;
		$bval = ($rgb & 0xFF) >> RSHIFT;
		$index = getColorIndex($rval, $gval, $bval);
		$histo[$index] = (isset($histo[$index]) ? $histo[$index] : 0) + 1;
	}
	return $histo;
}

function vboxFromPixels($pixels, array $histo) {
	$rmin = 1000000;
	$rmax = 0;
	$gmin = 1000000;
	$gmax = 0;
	$bmin = 1000000;
	$bmax = 0;
	
	// find min/max
	foreach ($pixels as $rgb) {
		$rval = (($rgb >> 16) & 0xFF) >> RSHIFT;
		$gval = (($rgb >> 8) & 0xFF) >> RSHIFT;
		$bval = ($rgb & 0xFF) >> RSHIFT;
		if ($rval < $rmin)
			$rmin = $rval;
		else if ($rval > $rmax)
			$rmax = $rval;
		
		if ($gval < $gmin)
			$gmin = $gval;
		else if ($gval > $gmax)
			$gmax = $gval;
		
		if ($bval < $bmin)
			$bmin = $bval;
		else if ($bval > $bmax)
			$bmax = $bval;
	}
	;
	
	return new VBox($rmin, $rmax, $gmin, $gmax, $bmin, $bmax, $histo);
}

function doCut($color, $vbox, $partialsum, $total, $lookaheadsum) {
	$dim1 = $color . '1';
	$dim2 = $color . '2';
	$count2 = 0;
	
	for($i = $vbox->$dim1; $i <= $vbox->$dim2; $i++) {
		if ($partialsum[$i] > $total / 2) {
			$vbox1 = $vbox->copy();
			$vbox2 = $vbox->copy();
			$left = $i - $vbox->$dim1;
			$right = $vbox->$dim2 - $i;
			
			if ($left <= $right)
				$d2 = min($vbox->$dim2 - 1, ~ ~ ($i + $right / 2));
			else
				$d2 = max($vbox->$dim1, ~ ~ ($i - 1 - $left / 2));
				// avoid 0-count boxes
				
			while (empty($partialsum[$d2]))
				$d2 ++;
			
			$count2 = $lookaheadsum[$d2];
			while (! $count2 && !empty($partialsum[$d2 - 1]))
				$count2 = $lookaheadsum[--$d2];
				// set dimensions
				
			$vbox1->$dim2 = $d2;
			$vbox2->$dim1 = $vbox1->$dim2 + 1;
			
			// echo 'vbox counts: '.$vbox->count().' '.$vbox1->count().' '.$vbox2->count()."\n";
			return array($vbox1, $vbox2);
		}
	}
}

function medianCutApply($histo, $vbox) {
	if (!$vbox->count())
		return;
	
	$rw = $vbox->r2 - $vbox->r1 + 1;
	$gw = $vbox->g2 - $vbox->g1 + 1;
	$bw = $vbox->b2 - $vbox->b1 + 1;
	$maxw = max($rw, $gw, $bw);
	
	// only one pixel, no split
	if ($vbox->count() == 1) {
		return array ($vbox->copy());
	}
	
	/* Find the partial sum arrays along the selected axis. */
	$total = 0;
	$partialsum = array ();
	$lookaheadsum = array ();
	
	if ($maxw == $rw) {
		for($i = $vbox->r1; $i <= $vbox->r2; $i++) {
			$sum = 0;
			for($j = $vbox->g1; $j <= $vbox->g2; $j++) {
				for($k = $vbox->b1; $k <= $vbox->b2; $k++) {
					$index = getColorIndex($i, $j, $k);
					if (isset($histo[$index]))
						$sum += $histo[$index];
				}
			}
			$total += $sum;
			$partialsum[$i] = $total;
		}
	} else if ($maxw == $gw) {
		for($i = $vbox->g1; $i <= $vbox->g2; $i++) {
			$sum = 0;
			for($j = $vbox->r1; $j <= $vbox->r2; $j++) {
				for($k = $vbox->b1; $k <= $vbox->b2; $k++) {
					$index = getColorIndex($j, $i, $k);
					if (isset($histo[$index]))
						$sum += $histo[$index];
				}
			}
			$total += $sum;
			$partialsum[$i] = $total;
		}
	} else { /* maxw == bw */
		for($i = $vbox->b1; $i <= $vbox->b2; $i++) {
			$sum = 0;
			for($j = $vbox->r1; $j <= $vbox->r2; $j++) {
				for($k = $vbox->g1; $k <= $vbox->g2; $k++) {
					$index = getColorIndex($j, $k, $i);
					if (isset($histo [$index]))
						$sum += $histo[$index];
				}
			}
			$total += $sum;
			$partialsum[$i] = $total;
		}
	}
	
	foreach($partialsum as $i => $d) {
		$lookaheadsum[$i] = $total - $d;
	}
	
	// determine the cut planes
	if ($maxw == $rw)
		return doCut('r', $vbox, $partialsum, $total, $lookaheadsum);
	else if ($maxw == $gw)
		return doCut('g', $vbox, $partialsum, $total, $lookaheadsum);
	else
		return doCut('b', $vbox, $partialsum, $total, $lookaheadsum);
}

// inner function to do the iteration
function quantize_iter(&$lh, $target, $histo) {
	$ncolors = 1;
	$niters = 0;
	
	while ($niters < MAX_ITERATIONS) {
		$vbox = $lh->pop();
		
		if (! $vbox->count()) { /* just put it back */
			$lh->push($vbox);
			$niters++;
			continue;
		}
		// do the cut
		$vboxes = medianCutApply($histo, $vbox);
		$vbox1 = $vboxes[0];
		$vbox2 = $vboxes[1];
		
		if (! $vbox1) {
			// echo "vbox1 not defined; shouldn't happen!"."\n";
			return;
		}
		
		$lh->push($vbox1);
		if ($vbox2) { /* vbox2 can be null */
			$lh->push($vbox2);
			$ncolors++;
		}
		if ($ncolors >= $target)
			return;
		if ($niters++ > MAX_ITERATIONS) {
			// echo "infinite loop; perhaps too few pixels!"."\n";
			return;
		}
	}
}

function quantize($pixels, $maxcolors) {
	// short-circuit
	if (! count($pixels) || $maxcolors < 2 || $maxcolors > 256) {
		// echo 'wrong number of maxcolors'."\n";
		return false;
	}
	
	$histo = getHisto($pixels);
	$histosize = 1 << (3 * SIGBITS);
	
	// check that we aren't below maxcolors already
	if (count($histo) <= $maxcolors) {
		// XXX: generate the new colors from the histo and return
	}
	
	$vbox = vboxFromPixels($pixels, $histo);
	
	$pq = new PQueue(function($a, $b) {
		return naturalOrder($a->count(), $b->count());
	});
	$pq->push($vbox);
	
	// first set of colors, sorted by population
	quantize_iter($pq, FRACT_BY_POPULATIONS * $maxcolors, $histo);
	
	// Re-sort by the product of pixel occupancy times the size in color space.
	$pq2 = new PQueue(function($a, $b) {
		return naturalOrder($a->count () * $a->volume (), $b->count () * $b->volume ());
	} );
	
	// TODO Replace "size" by a new function "empty" or, better, by a counter
	while ($pq->size()) {
		$pq2->push($pq->pop());
	}
	
	// next set - generate the median cuts using the (npix * vol) sorting.
	quantize_iter($pq2, $maxcolors - $pq2->size(), $histo);
	
	// calculate the actual colors
	$cmap = new CMap();
	// TODO Replace "size" by a new function "empty" or, better, by a counter
	while ($pq2->size()) {
		$cmap->push($pq2->pop());
	}
	
	return $cmap;
}

class ColorThiefPHP {
	/*
	 * getColor(sourceImage[, quality])
	 * returns {r: num, g: num, b: num}
	 * 
	 * Use the median cut algorithm to cluster similar colors and 
	 * return the base color from the largest cluster. Quality is 
	 * an optional argument. It needs to be an integer. 
	 * 0 is the highest quality settings. 10 is the default. 
	 * There is a trade-off between quality and speed. 
	 * The bigger the number, the faster a color will be returned 
	 * but the greater the likelihood that it will not be the 
	 * visually most dominant color.
	 * 
	 */
	public static function getColor($sourceImage, $quality = 10) {
		$palette = $this->getPalette($sourceImage, 5, $quality);
		return $palette[0];
	}
	
	/*
	 * getPalette(sourceImage[, colorCount, quality])
	 * returns array[ {r: num, g: num, b: num}, {r: num, g: num, b: num}, ...]
	 * 
	 * Use the median cut algorithm to cluster similar colors.
	 * 
	 * colorCount determines the size of the palette; the number of colors 
	 * returned. If not set, it defaults to 10.
	 * 
	 * BUGGY: Function does not always return the requested amount of colors.
	 * It can be +/- 2. 
	 * 
	 * quality is an optional argument. It needs to be an integer. 
	 * 0 is the highest quality settings. 10 is the default. 
	 * There is a trade-off between quality and speed. The bigger the number,
	 * the faster the palette generation but the greater the likelihood that 
	 * colors will be missed.
	 */
	public static function getPalette($sourceImage, $colorCount = 10, $quality = 10) {
		$ext = strtolower(pathinfo($sourceImage, PATHINFO_EXTENSION));
		
		if ($ext == 'jpg' || $ext == 'jpeg')
			$image = imagecreatefromjpeg ($sourceImage);
		if ($ext == 'gif')
			$image = imagecreatefromgif($sourceImage);
		if ($ext == 'png')
			$image = imagecreatefrompng ($sourceImage);
		
		$width = imagesx($image);
		$height = imagesy($image);
		$pixelCount = $width * $height;
		
		// Store the RGB values in an array format suitable for quantize function
		if(class_exists("SplFixedArray"))
			// SplFixedArray is faster and more memory-efficient than normal PHP array.
			// Uses it if available.
			$pixelArray = new SplFixedArray(ceil($pixelCount/$quality));
		else 
			$pixelArray = array();
		
		$j = 0;
		for($i = 0; $i < $pixelCount; $i = $i + $quality) {
			$x = $i % $width;
			$y = (int) ($i / $width);
			$rgba = imagecolorat($image, $x, $y);
			$colors = imagecolorsforindex($image, $rgba);
			$alpha = $colors['alpha'];
			$rgb = getColorIndex($colors['red'], $colors['green'], $colors['blue'], 8);
			
			// If pixel is mostly opaque and not white
			if ($alpha <= 62) {
				if (! ($colors['red'] > 250 && $colors['green'] > 250 && $colors['blue'] > 250)) {
					$pixelArray[$j++] = $rgb;
				}
			}
		}
		
		if(class_exists("SplFixedArray"))
			$pixelArray->setSize($j);
		
		imagedestroy($image);
		
		// Send array to quantize function which clusters values
		// using median cut algorithm
		$cmap = quantize($pixelArray, $colorCount);
		$palette = $cmap->palette();
		
		return $palette;
	}
}

?>