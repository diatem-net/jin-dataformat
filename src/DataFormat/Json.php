<?php

/**
 * Jin Framework
 * Diatem
 */

namespace Jin2\DataFormat;

/**
 * Gestion de chaînes JSON
 */
class Json
{

  /**
   * Encode un tableau au format JSON
   *
   * @param  array   $data              Données à encoder
   * @param  boolean $convertIntoString (optional) Si TRUE, convertit préalablement en chaîne de caractère toutes les valeurs
   * @return string
   */
  public static function encode($data, $convertIntoString = false)
  {
    if ($convertIntoString){
      return static::encodeWithStringConvert($data);
    } else {
      return json_encode($data);
    }
  }

  /**
   * Décode des données JSON en un tableau
   *
   * @param  string $data  Données JSON
   * @return array         NULL si une erreur survient
   */
  public static function decode($data)
  {
    return json_decode($data, true);
  }

  /**
   * Retourne le dernier code d'erreur retourné
   *
   * @return int
   */
  public static function getLastErrorCode()
  {
    return json_last_error();
  }

  /**
   * Retourne le dernier message d'erreur retourné (verbose)
   *
   * @return string
   */
  public static function getLastErrorVerbose()
  {
    switch (json_last_error()) {
      case JSON_ERROR_NONE:
        return json_last_error().' - Aucune erreur';
        break;
      case JSON_ERROR_DEPTH:
        return json_last_error().' - Profondeur maximale atteinte';
        break;
      case JSON_ERROR_STATE_MISMATCH:
        return json_last_error().' - Inadéquation des modes ou underflow';
        break;
      case JSON_ERROR_CTRL_CHAR:
        return json_last_error().' - Erreur lors du contrôle des caractères';
        break;
      case JSON_ERROR_SYNTAX:
        return json_last_error().' - Erreur de syntaxe ; JSON malformé';
        break;
      case JSON_ERROR_UTF8:
        return json_last_error().' - Caractères UTF-8 malformés, probablement une erreur d\'encodage';
        break;
      default:
        return json_last_error().' - Erreur inconnue';
    }
  }

  /**
   * Effectue un encodage en Json en transformant toutes les valeurs en chaînes de caractères.
   *
   * @param  array $array  Données à encoder
   * @return string
   */
  protected static function encodeWithStringConvert($array)
  {
    return json_encode(static::convert($array), JSON_HEX_QUOT | JSON_HEX_TAG);
  }

  /**
   * Convertit toutes les valeurs d'un tableau en chaîne (recursif)
   *
   * @param  array $array  Tableau à encoder
   * @return string
   */
  protected static function convert($array)
  {
    if (is_array($array)) {
      foreach($array AS $key => $value) {
        $array[$key] = static::convert($value);
      }
    } else {
      return (string) $array;
    }
    return $array;
  }

}
