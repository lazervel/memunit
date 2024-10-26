<?php declare(strict_types=1);

namespace MemUnit;

use MemUnit\Exception\InvalidUnitFormatException;
use MemUnit\Exception\InvalidRangeBytesException;

class MemUnit
{
  private const MEM_UNITS = ['Byte', 'Kilo', 'Mega', 'Giga', 'Tera', 'Peta', 'Exa', 'Zetta', 'Yotta'];
  private const RUNIT_FORMAT = '/^(?:((?:B|D)F)|(S|B|D)|(s|b|d))$/';
  private const MAX_MEMUNIT  = 1.2379400392854E+27;

  /**
   * Reset peak memory usage before a new operation
   * 
   * @return void
   */
  public static function reset() : void
  {
    \memory_reset_peak_usage();
  }

  /**
   * Returns the peak of memory allocated by PHP
   * 
   * @param bool $realUsage [optional]
   * @param bool $formated  [optional]
   * 
   * @return int|string Peak of Memory
   */
  public static function peakUsage(bool $realUsage = false, bool $formated = false)
  {
    $bytes = memory_get_peak_usage($realUsage);
    return $formated ? self::format($bytes) : $bytes;
  }

  /**
   * Returns the amount of memory allocated to PHP
   * 
   * @param bool $realUsage [optional]
   * @param bool $formated  [optional]
   * 
   * @return int|string Amount of Memory
   */
  public static function usage(bool $realUsage = false, bool $formated = false)
  {
    $bytes = memory_get_usage($realUsage);
    return $formated ? self::format($bytes) : $bytes;
  }

  /**
   * 
   * 
   * @param float|int $bytes      [required]
   * @param string    $unitFormat [optional]
   * 
   * @return string formated memory Units
   */
  public static function bitFormat(float $bits, string $unitFormat = 'D') : string
  {
    $format = [];
    self::checkUnitFormat($unitFormat, $format)->validateBytesRange($bits);
    $factor = floor(log($bits, 1000));

    return self::doFormat(round($bits / pow(1000, $factor), 3), self::MEM_UNITS[$factor], $format, 'bit');
  }
  
  /**
   * 
   * 
   * @param float|int $bytes      [required]
   * @param string    $unitFormat [optional]
   * 
   * @return string formated memory Units
   */
  public static function byteFormat(float $bytes, string $unitFormat = 'D') : string
  {
    $format = [];
    self::checkUnitFormat($unitFormat, $format)->validateBytesRange($bytes);
    $factor = floor(log($bytes, 1024));

    return self::doFormat(round($bytes / pow(1024, $factor), 3), self::MEM_UNITS[$factor], $format, 'byte');
  }

  public static function format(float $value, bool $formatBits = false, string $unitFormat = 'D') : string
  {
    return $formatBits ? self::bitFormat($value, $unitFormat) : self::byteFormat($value, $unitFormat);
  }

  /**
   * 
   * 
   * @param string $unitFormat [required]
   * @param array  $format     [required]
   * @return \MemUnit\MemUnit
   */
  private static function checkUnitFormat(string $unitFormat, array &$format) : self
  {
    if (!preg_match(self::RUNIT_FORMAT, $unitFormat, $format)) {
      throw new InvalidUnitFormatException(sprintf('Cannot format invalid unit format [%s].', $unitFormat));
    }
    return new self;
  }

  /**
   * 
   * 
   * @param float|int $quantity   [required]
   * @param string    $unit       [required]
   * @param array     $format     [required]
   * 
   * @return string 
   */
  private static function doFormat(float $quantity, string $unit, array $format, string $type) : string
  {
    if (self::MEM_UNITS[0] === $unit) {
      $bytes = ucfirst($type);
      if ($quantity > 1) {
        $bytes .= 's';
      }
    }

    $formates = [
      'BF' => $bytes ?? (substr($unit, 0, 2).'bi'.$type),
      'DF' => $bytes ?? ($unit.$type),
      'S'  => $bytes[0] ?? $unit[0],
      'D'  => $bytes[0] ?? ($unit[0].'B'),
      'B'  => $bytes[0] ?? ($unit[0].'IB')
    ];

    $unit = $formates[strtoupper($format[0])];
    return sprintf('%g %s', round(floor($quantity * 100) / 100, 2), isset($format[3]) ? strtolower($unit) : $unit);
  }

  /**
   * 
   * 
   * @param float|int $bytes [required]
   * @return void
   */
  private static function validateBytesRange(float $bytes) : void
  {
    if ($bytes < 0 || $bytes > self::MAX_MEMUNIT) {
      throw new InvalidRangeBytesException(sprintf('Invalid bytes of range [%d] Allow bytes [1B - 1024YB].'), $bytes);
    }
  }
}
?>