<?php

/**
 * Jin Framework
 * Diatem
 */

namespace Jin2\DataFormat;

use Jin2\Db\Query\QueryResult;

/**
 * Gestion de flux CSV
 */
class Csv
{

  /**
   * Données lues/à écrire
   *
   * @var array
   */
  protected $data;

  /**
   * Définit si on écrit les en-têtes de colonne ou non
   *
   * @var boolean
   */
  protected $useHeaders = true;

  /**
   * Définit si on utilise un tableau associatif ou non
   *
   * @var boolean
   */
  protected $useAssociativeArray = false;

  /**
   * Caractère pour protéger les champs
   *
   * @var string
   */
  protected $enclosures = '"';

  /**
   * Constructeur
   */
  public function __construct()
  {
  }

  /**
   * Définit les données à écrire à partir d'un tableau associatif multidimensionnel.
   *
   * @param array   $array                Tableau de données
   * @param boolean $useHeaders           Affiche les en-tête de colonne ou non
   * @param boolean $useAssociativeArray  Usage d'un tableau associatif ou non
   */
  public function populateWithArray($array, $useHeaders = true, $useAssociativeArray = true)
  {
    $this->data = $array;
    $this->useHeaders = $useHeaders;
    $this->useAssociativeArray = $useAssociativeArray;
  }

  /**
   * Définit les données à écrire à partir d'un objet QueryResult
   *
   * @param QueryResult $queryResult
   * @param boolean $useHeaders Affiche les en-tête de colonne ou non
   */
  public function populateWithQueryResult(QueryResult $queryResult, $useHeaders = true)
  {
    $this->data = $queryResult->getDatasInArray(true, true);
    $this->useHeaders = $useHeaders;
    $this->useAssociativeArray = true;
  }

  /**
   * Ecrit le flux CSV dans un fichier
   *
   * @param  string $filePath    Chemin relatif/absolu du fichier
   * @param  string $enclosures  Caracère utiliser pour protéger les champs. Si null = aucun caractère.
   * @throws \Exception
   */
  public function outputInFile($filePath, $enclosures = '"')
  {
    $this->enclosures = $enclosures;
    if (!$this->data) {
      throw new \Exception('Aucune donnée CSV à exporter.');
    }

    // On cherche des infos sur le fichier à ouvrir
    if (file_exists($filePath)) {
      $infos_fichier = stat($filePath);
    }

    // Si le fichier est inexistant ou vide, on va le créer et y ajouter les
    // libellés de colonne.
    if (!file_exists($filePath) || (isset($infos_fichier) && $infos_fichier['size'] == 0)) {

      // On ouvre le fichier en écriture seule et on le vide de son contenu
      $fp = @fopen($filePath, 'w');
      if ($fp === false) {
        throw new \Exception("Le fichier ${$filePath} n'a pas pu être créé.");
      }

      if ($this->useHeaders) {
        // Les entêtes sont les clés du tableau associatif
        $entetes = array_keys($this->data[0]);

        // Décodage des entêtes qui sont en UTF8 à la base
        foreach ($entetes as &$entete) {
          $entete = (is_string($entete)) ? iconv("UTF-8", "Windows-1252//TRANSLIT", $entete) : $entete;
        }

        // On utilise le troisième paramètre de fputcsv pour changer le séparateur par défaut de php
        $this->fputcsv($fp, $entetes, ';', $this->enclosures);
      }
    }

    // On ouvre le handler en écriture pour écrire le fichier
    // s'il ne l'est pas déjà.
    if (!isset($fp)) {
      $fp = fopen($filePath, 'a');
    }

    $this->writeDataInIO($fp);
    fclose($fp);
  }

  /**
   * Ecrit le flux CSV dans la sortie navigateur
   *
   * @param  string $fileName    Nom du fichier généré
   * @param  string $enclosures  Caracère utiliser pour protéger les champs. Si null = aucun caractère.
   * @throws \Exception
   */
  public function output($fileName, $enclosures = '"')
  {
    $this->enclosures = $enclosures;
    if (!$this->data) {
      throw new \Exception('Aucune donnée CSV à exporter.');
    }

    header('Content-Type: application/excel');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');

    $fp = fopen('php://output', 'w');
    if ($this->useHeaders) {
      // Les entêtes sont les clés du tableau associatif
      $entetes = array_keys($this->data[0]);

      // Décodage des entêtes qui sont en UTF8 à la base
      foreach ($entetes as &$entete) {
        $entete = (is_string($entete)) ? iconv("UTF-8", "Windows-1252//TRANSLIT", $entete) : $entete;
      }

      // On utilise le troisième paramètre de fputcsv pour changer le séparateur par défaut de php
      fputcsv($fp, $entetes, ';', $this->enclosures);
    }

    // Écriture des données
    $this->writeDataInIO($fp);
    fclose($fp);
  }

  /**
   * Write a line to a file
   *
   * @param string $filePointer  File resource to write in
   * @param array  $dataArray    Data to write out
   * @param string $delimiter    Field separator
   * @param string $enclosure    Enclosure
   */
  protected function fputcsv($filePointer, $dataArray, $delimiter = ",",   $enclosure = "\"")
  {
    // Build the string
    $string = "";
    // No leading delimiter
    $writeDelimiter = false;
    foreach ($dataArray as $dataElement) {
      // Replaces a double quote with two double quotes
      $dataElement = str_replace("\"", "\"\"", $dataElement);
      // Adds a delimiter before each field (except the first)
      if ($writeDelimiter) {
        $string .= $delimiter;
      }
      // Encloses each field with $enclosure and adds it to the string
      if ($enclosure) {
        $string .= $enclosure . $dataElement . $enclosure;
      } else {
        $string .= $dataElement;
      }
      // Delimiters are used every time except the first.
      $writeDelimiter = true;
    }
    // Append new line
    $string .= "\n";
    // Write the string to the file
    fwrite($filePointer, $string);
  }

  /**
   * Ecrit dans le flux de sortie
   *
   * @param ressource $fp
   */
  protected function writeDataInIO($fp)
  {
    // Écriture des données
    if ($this->useAssociativeArray) {
      foreach ($this->data as $donnee) {
        foreach ($donnee as &$champ) {
          $champ = (is_string($champ)) ? iconv("UTF-8", "Windows-1252//TRANSLIT", $champ) : $champ;
        }
        $this->fputcsv($fp, $donnee, ';', $this->enclosures);
      }
    } else {
      for ($i = 0; $i < count($this->data); $i++) {
        for ($j = 0; $j < count($this->data[$i]); $j++) {
          $this->fputcsv($fp, $this->data[$i][$j], ';', $this->enclosures);
        }
      }
    }
  }

}
