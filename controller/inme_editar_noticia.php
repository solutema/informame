<?php

/*
 * This file is part of informame
 * Copyright (C) 2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('inme_noticia_fuente.php');
require_model('inme_tema.php');

/**
 * Description of inme_editar_noticia
 *
 * @author carlos
 */
class inme_editar_noticia extends fs_controller
{
   public $noticia;
   public $relacionada;
   public $temas;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Editar noticia', 'informame', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      $this->noticia = FALSE;
      $this->relacionada = FALSE;
      $this->temas = array();
      
      if( isset($_REQUEST['id']) )
      {
         $noti0 = new inme_noticia_fuente();
         $this->noticia = $noti0->get($_REQUEST['id']);
      }
      
      if($this->noticia)
      {
         if( isset($_POST['url']) )
         {
            $this->noticia->editada = TRUE;
            $this->noticia->url = $_POST['url'];
            $this->noticia->titulo = $_POST['titulo'];
            $this->noticia->resumen = substr($_POST['resumen'], 0, 300);
            $this->noticia->texto = $_POST['texto'];
            
            $this->noticia->id_relacionada = null;
            if($_POST['id_relacionada'] != '')
            {
               $this->noticia->id_relacionada = intval($_POST['id_relacionada']);
            }
            
            $this->noticia->preview = $_POST['preview'];
            
            $this->noticia->clean_keywords();
            $keys = explode(',', $_POST['keywords']);
            if($keys)
            {
               $tema0 = new inme_tema();
               
               foreach($keys as $k)
               {
                  if($k != '')
                  {
                     $codtema = $this->sanitize_url($k, 50);
                     
                     $tema = $tema0->get($codtema);
                     if(!$tema)
                     {
                        $tema = new inme_tema();
                        $tema->codtema = $codtema;
                        $tema->titulo = $tema->texto = $k;
                     }
                     
                     if( $tema->save() )
                     {
                        $this->noticia->set_keyword($codtema);
                     }
                  }
               }
            }
            
            if( $this->noticia->save() )
            {
               $this->new_message('Datos modificados correctamente.');
            }
            else
               $this->new_error_msg('Error al guardar los datos.');
         }
         
         if( !is_null($this->noticia->id_relacionada) )
         {
            $this->relacionada = $noti0->get($this->noticia->id_relacionada);
         }
         
         $tema0 = new inme_tema();
         foreach($this->noticia->keywords() as $key)
         {
            $tema = $tema0->get($key);
            if($tema)
            {
               if($tema->activo)
               {
                  $this->temas[] = $tema;
               }
            }
            else
            {
               $this->new_error_msg('Tema '.$key.' no encontrado.');
            }
         }
      }
      else
         $this->new_error_msg('Noticia no encontrada.');
   }
   
   private function sanitize_url($text, $len = 85)
   {
      $text = strtolower( $this->true_text_break($text, $len) );
      $changes = array('/à/' => 'a', '/á/' => 'a', '/â/' => 'a', '/ã/' => 'a', '/ä/' => 'a',
          '/å/' => 'a', '/æ/' => 'ae', '/ç/' => 'c', '/è/' => 'e', '/é/' => 'e', '/ê/' => 'e',
          '/ë/' => 'e', '/ì/' => 'i', '/í/' => 'i', '/î/' => 'i', '/ï/' => 'i', '/ð/' => 'd',
          '/ñ/' => 'n', '/ò/' => 'o', '/ó/' => 'o', '/ô/' => 'o', '/õ/' => 'o', '/ö/' => 'o',
          '/ő/' => 'o', '/ø/' => 'o', '/ù/' => 'u', '/ú/' => 'u', '/û/' => 'u', '/ü/' => 'u',
          '/ű/' => 'u', '/ý/' => 'y', '/þ/' => 'th', '/ÿ/' => 'y', '/ñ/' => 'ny',
          '/&quot;/' => '-', '/&#39;/' => ''
      );
      $text = preg_replace(array_keys($changes), $changes, $text);
      $text = preg_replace('/[^a-z0-9]/i', '-', $text);
      $text = preg_replace('/-+/', '-', $text);
      
      if( substr($text, 0, 1) == '-' )
         $text = substr($text, 1);
      
      if( substr($text, -1) == '-' )
         $text = substr($text, 0, -1);
      
      return $text;
   }
   
   private function true_text_break($str, $max_t_width=500)
   {
      $desc = $this->noticia->no_html($str);
      
      if( mb_strlen($desc) <= $max_t_width )
      {
         return trim($desc);
      }
      else
      {
         $description = '';
         
         foreach(explode(' ', $desc) as $aux)
         {
            if( mb_strlen($description.' '.$aux) < $max_t_width-3 )
            {
               if($description == '')
               {
                  $description = $aux;
               }
               else
                  $description .= ' ' . $aux;
            }
            else
               break;
         }
         
         return trim($description).'...';
      }
   }
}