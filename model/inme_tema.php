<?php

/*
 * This file is part of informame
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of inme_tema
 *
 * @author carlos
 */
class inme_tema extends fs_model
{
   /**
    * Clave primaria. Varchar (50)
    * @var type 
    */
   public $codtema;
   public $titulo;
   public $texto;
   public $imagen;
   public $articulos;
   public $popularidad;
   public $activo;
   public $fecha;
   
   /// texto a buscar en noticias para asignarles este tema
   public $busqueda;
   
   private $keywords;
   
   public function __construct($t = FALSE)
   {
      parent::__construct('inme_temas');
      if($t)
      {
         $this->codtema = $t['codtema'];
         $this->titulo = $t['titulo'];
         $this->texto = $t['texto'];
         $this->keywords = $t['keywords'];
         $this->imagen = $t['imagen'];
         $this->articulos = intval($t['articulos']);
         $this->popularidad = intval($t['popularidad']);
         $this->activo = $this->str2bool($t['activo']);
         $this->fecha = date('d-m-Y', strtotime($t['fecha']));
         $this->busqueda = $t['busqueda'];
      }
      else
      {
         $this->codtema = NULL;
         $this->titulo = '';
         $this->texto = '';
         $this->keywords = '';
         $this->imagen = NULL;
         $this->articulos = 0;
         $this->popularidad = 0;
         $this->activo = TRUE;
         $this->fecha = date('d-m-Y');
         $this->busqueda = '';
      }
   }
   
   protected function install()
   {
      return "INSERT INTO inme_temas (codtema,titulo,texto,keywords,imagen,activo) VALUES"
              . " ('espanya','España','España (Reino de España)','[espanya],[spain]','https://i.imgur.com/nDoxKF3.jpg',true)"
              . ",('corrupcion','Corrupción','Corrupción','[corrupcion]','https://i.imgur.com/wYe54PC.jpg',true)"
              . ",('ee-uu','EE.UU','Estados Unidos de América','[estados-unidos],[usa],[ee-uu],[eeuu]','https://i.imgur.com/MsZyxdq.jpg',true)"
              . ",('alemania','Alemania','Alemania','[alemania]','https://i.imgur.com/I8f9WXM.jpg',true)"
              . ",('china','China','China','[china]','https://i.imgur.com/T5KsW3L.jpg',true)"
              . ",('grafeno','Grafeno','Grafeno','[grafeno]','https://i.imgur.com/jjlcWYu.jpg',true)"
              . ",('grecia','Grecia','Grecia','[grecia]','https://i.imgur.com/FyyQJho.jpg',true)"
              . ",('isis','ISIS','ISIS','[isis]','https://i.imgur.com/qXgdYox.jpg',true)"
              . ",('israel','Israel','Israel','[israel]','https://i.imgur.com/2uRAhdA.png',true)"
              . ",('linux','Linux','Linux','[linux]','https://i.imgur.com/zF5yVoQ.png',true)"
              . ",('rusia','Rusia','Rusia','[rusia]','https://i.imgur.com/7WZu7fl.jpg',true)"
              . ",('venezuela','Venezuela','Venezuela','[venezuela]','https://i.imgur.com/jAB2UDd.jpg',true)"
              . ",('microsoft','Microsoft','Microsoft','[microsoft]','https://i.imgur.com/LLX8ddu.jpg',true)"
              . ",('google','Google','Google','[google]','https://i.imgur.com/Gh7Ib2o.png',true)"
              . ",('apple','Apple','Apple','[apple]','https://i.imgur.com/Qttksz6.jpg',true)"
              . ",('nazis','Nazismo','Nazismo','[nazis],[nazismo],[hitler]','https://i.imgur.com/WYdIkd8.png',true)"
              . ",('pp','Partido Popular','Partido Popular','[pp]','https://i.imgur.com/IjmbCcA.jpg',true)"
              . ",('psoe','PSOE','Partido Socialista Obrero Español','[psoe]','https://tse1.mm.bing.net/th?id=OIP.M6c9b84c4b1a61d81117823f84adbf69ao0&w=230&h=170&rs=1&pcl=dddddd&pid=1.1',true)"
              . ",('podemos','Podemos','Podemos','[podemos]','https://pbs.twimg.com/profile_images/478483096598097920/4lnBU17e_bigger.jpeg',true)"
              . ",('raspberry-pi','raspberry-pi','raspberry-pi','[raspberry]','https://i.imgur.com/RZ7iexA.jpg',true)"
              . ",('ubuntu','Ubuntu','Ubuntu','[ubuntu]','https://i.imgur.com/3Sz1WVo.png',true)"
              . ";";
   }
   
   public function url()
   {
      return 'index.php?page=inme_editar_tema&cod='.$this->codtema;
   }
   
   public function texto($len = 0)
   {
      if($len == 0)
      {
         return nl2br($this->texto);
      }
      else
      {
         return nl2br( mb_substr($this->texto, 0, $len) );
      }
   }
   
   public function keywords($plain = FALSE)
   {
      $keys = array();
      
      $aux = explode(',', $this->keywords);
      if($aux)
      {
         foreach($aux as $i => $value)
         {
            $key = str_replace( array('[',']') , array('',''), $value);
            if($key)
            {
               $keys[] = $key;
            }
         }
      }
      
      if($plain)
      {
         return join(', ', $keys);
      }
      else
      {
         return $keys;
      }
   }
   
   public function set_keyword($k)
   {
      if($this->keywords == '')
      {
         $this->keywords = '['.strtolower($k).']';
      }
      else if( !in_array( $k, $this->keywords() ) )
      {
         $this->keywords .= ',['.strtolower($k).']';
      }
   }
   
   public function clean_keywords()
   {
      $this->keywords = NULL;
   }
   
   public function busquedas()
   {
      $keys = array();
      
      $aux = explode(',', $this->busqueda);
      if($aux)
      {
         foreach($aux as $value)
         {
            $key = trim($value);
            if($key)
            {
               $keys[] = $key;
            }
         }
      }
      
      return $keys;
   }
   
   public function get($cod)
   {
      $data = $this->db->select("SELECT * FROM inme_temas WHERE codtema = ".$this->var2str($cod).";");
      if($data)
      {
         return new inme_tema($data[0]);
      }
      else
      {
         $cod = $this->db->escape_string($cod);
         $data = $this->db->select("SELECT * FROM inme_temas WHERE keywords LIKE '%[".$cod."]%';");
         if($data)
         {
            return new inme_tema($data[0]);
         }
         else
         {
            return FALSE;
         }
      }
   }
   
   public function exists()
   {
      if( is_null($this->codtema) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM inme_temas WHERE codtema = ".$this->var2str($this->codtema).";");
      }
   }
   
   public function save()
   {
      if( strlen($this->codtema) > 1 AND strlen($this->codtema) <= 50 )
      {
         if(!$this->activo)
         {
            $this->popularidad = 0;
         }
         
         if( substr($this->imagen, 0, 18) == 'http://i.imgur.com' )
         {
            /// cambiamos http por https para las imágenes de imgur
            $this->imagen = str_replace('http://i.imgur.com', 'https://i.imgur.com', $this->imagen);
         }
         else if( substr($this->imagen, 0, 7) == 'http://' )
         {
            $this->imagen = NULL;
            $this->new_error_msg('Ya no se admiten imágenes http. Imagen eliminada.');
         }
         
         if( $this->exists() )
         {
            $sql = "UPDATE inme_temas SET titulo = ".$this->var2str($this->titulo)
                    .", texto = ".$this->var2str($this->texto)
                    .", keywords = ".$this->var2str($this->keywords)
                    .", imagen = ".$this->var2str($this->imagen)
                    .", articulos = ".$this->var2str($this->articulos)
                    .", popularidad = ".$this->var2str($this->popularidad)
                    .", activo = ".$this->var2str($this->activo)
                    .", fecha = ".$this->var2str($this->fecha)
                    .", busqueda = ".$this->var2str($this->busqueda)
                    ."  WHERE codtema = ".$this->var2str($this->codtema).";";
         }
         else
         {
            $sql = "INSERT INTO inme_temas (codtema,titulo,texto,keywords,imagen,articulos,activo,fecha,"
                    . "popularidad,busqueda) VALUES "
                    . "(".$this->var2str($this->codtema)
                    . ",".$this->var2str($this->titulo)
                    . ",".$this->var2str($this->texto)
                    . ",".$this->var2str($this->keywords)
                    . ",".$this->var2str($this->imagen)
                    . ",".$this->var2str($this->articulos)
                    . ",".$this->var2str($this->activo)
                    . ",".$this->var2str($this->fecha)
                    . ",".$this->var2str($this->popularidad)
                    . ",".$this->var2str($this->busqueda).");";
         }
         
         return $this->db->exec($sql);
      }
      else
      {
         $this->new_error_msg('Código del tema no válido: '.$this->codtema
                 .'. Debe tener entre 1 y 50 caracteres.');
         
         return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM inme_temas WHERE codtema = ".$this->var2str($this->codtema).";");
   }
   
   public function all($offset = 0, $order = 'lower(titulo) ASC')
   {
      $tlist = array();
      
      $data = $this->db->select_limit("SELECT * FROM inme_temas ORDER BY ".$order, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
         {
            $tlist[] = new inme_tema($d);
         }
      }
      
      return $tlist;
   }
   
   public function populares($offset = 0)
   {
      $tlist = array();
      
      $data = $this->db->select_limit("SELECT * FROM inme_temas WHERE activo ORDER BY popularidad DESC, titulo DESC", FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
         {
            $tlist[] = new inme_tema($d);
         }
      }
      
      return $tlist;
   }
   
   public function random()
   {
      $tlist = array();
      $sql = "SELECT * FROM inme_temas WHERE activo ORDER BY ";
      if( strtolower(FS_DB_TYPE) == 'mysql' )
      {
         $sql .= 'rand()';
      }
      else
      {
         $sql .= ' popularidad DESC';
      }
      
      $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, 0);
      if($data)
      {
         foreach($data as $d)
         {
            $tlist[] = new inme_tema($d);
         }
      }
      
      return $tlist;
   }
   
   public function count()
   {
      $data = $this->db->select("SELECT COUNT(codtema) as num FROM inme_temas;");
      if($data)
      {
         return intval($data[0]['num']);
      }
      else
      {
         return 0;
      }
   }
   
   public function cron_job()
   {
      if( $this->db->table_exists('inme_noticias_fuente') )
      {
         foreach($this->random() as $tema)
         {
            /// calculamos la fecha del primer artículo de este tema
            if($tema->activo)
            {
               $sql = "SELECT MIN(fecha) as fecha FROM inme_noticias_fuente WHERE keywords LIKE '%[".$tema->codtema."]%';";
               $data = $this->db->select($sql);
               if($data)
               {
                  $tema->fecha = date('d-m-Y', strtotime($data[0]['fecha']));
               }
            }
            
            /// calculamos el número de artículos de este tema
            $tema->articulos = 0;
            if($tema->activo)
            {
               $sql = "SELECT COUNT(*) as num FROM inme_noticias_fuente WHERE keywords LIKE '%[".$tema->codtema."]%';";
               $data = $this->db->select($sql);
               if($data)
               {
                  $tema->articulos = intval($data[0]['num']);
               }
            }
            
            /// calculamos la popularidad del tema
            $tema->popularidad = 0;
            if($tema->activo AND $tema->articulos > 1)
            {
               $sql = "SELECT SUM(popularidad) as total FROM inme_noticias_fuente WHERE keywords LIKE '%[".$tema->codtema."]%'"
                       . " AND fecha >= ".$this->var2str( date('d-m-Y', strtotime('-1 month')) ).";";
               $data = $this->db->select($sql);
               if($data)
               {
                  $dias = (time() - strtotime($tema->fecha)) / 86400;
                  if($dias > 0)
                  {
                     $tema->popularidad = intval($data[0]['total'] / $dias);
                  }
                  else
                  {
                     $tema->popularidad = intval($data[0]['total']);
                  }
               }
            }
            
            $tema->save();
         }
      }
   }
}
