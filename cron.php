<?php

/*
 * This file is part of informame
 * Copyright (C) 2015-2016  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_model('inme_fuente.php');
require_model('inme_noticia_fuente.php');
require_model('inme_noticia_preview.php');
require_model('inme_tema.php');

class inme_cron
{
   private $db;
   
   public function __construct(&$db)
   {
      $this->db = $db;
      
      /// Forzamos una llamada web para picar
      $empresa = new empresa();
      $this->curl_download($empresa->web.'/index.php?page=inme_picar&hidden=TRUE');
      
      /// procesamos noticias aleatorias
      $order = 'popularidad DESC';
      if( mt_rand(0, 1) == 0 )
      {
         $order = 'fecha DESC';
      }
      
      $noti0 = new inme_noticia_fuente();
      foreach($noti0->all( mt_rand(0, 100), $order ) as $noti)
      {
         $popularidad = $noti->popularidad();
         switch( mt_rand(0, 3) )
         {
            default:
               $this->preview_noticia($noti);
               break;
            
            case 0:
               $noti->tweets = max( array($noti->tweets, $this->tweet_count($noti->url)) );
               break;
            
            case 1:
               $noti->likes = max( array($noti->likes, $this->facebook_count($noti->url)) );
               break;
            
            case 2:
               $noti->meneos = max( array($noti->meneos, $this->meneame_count($noti->url)) );
               break;
         }
         
         if( $noti->popularidad() == $popularidad )
         {
            echo '=';
         }
         else
         {
            $noti->save();
            echo '.';
         }
      }
      
      /// comprobamos las fuentes
      $fuente0 = new inme_fuente();
      $fuente0->cron_job();
      
      $this->comprobar_temas();
      
      /// Por último forzamos una llamada web para picar
      $this->curl_download($empresa->web.'/index.php?page=inme_picar&hidden=TRUE');
   }
   
   private function tweet_count($link)
   {
      $json_string = $this->curl_download('http://urls.api.twitter.com/1/urls/count.json?url='.rawurlencode($link), FALSE);
      $json = json_decode($json_string, TRUE);
      
      return isset($json['count']) ? intval($json['count']) : 0;
   }
   
   private function facebook_count($link)
   {
      $json_string = $this->curl_download('http://api.facebook.com/restserver.php?method=links.getStats&format=json&urls='.
              rawurlencode($link), FALSE);
      $json = json_decode($json_string, TRUE);
      
      return isset($json[0]['total_count']) ? intval($json[0]['total_count']) : 0;
   }
   
   private function meneame_count($link)
   {
      $string = $this->curl_download('http://www.meneame.net/api/url.php?url='.rawurlencode($link), FALSE);
      $vars = explode(' ', $string);
      
      $meneos = 0;
      if( count($vars) == 4 )
      {
         $meneos = intval($vars[2]);
      }
      
      return $meneos;
   }
   
   public function curl_download($url, $googlebot=TRUE, $timeout=10)
   {
      $ch0 = curl_init($url);
      curl_setopt($ch0, CURLOPT_TIMEOUT, $timeout);
      curl_setopt($ch0, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch0, CURLOPT_FOLLOWLOCATION, true);
      
      if($googlebot)
      {
         curl_setopt($ch0, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
      }
      
      $html = curl_exec($ch0);
      curl_close($ch0);
      
      return $html;
   }
   
   /**
    * Busca imágentes/vídeos en la noticia.
    * @param inme_noticia_fuente $noti
    */
   private function preview_noticia(&$noti)
   {
      if($noti->editada)
      {
         /// si está editada, no hacemos nada
      }
      else if( is_null($noti->preview) )
      {
         /// primero intentamos asignar la imagen de un tema
         $tema0 = new inme_tema();
         foreach($noti->keywords() as $key)
         {
            $tema = $tema0->get($key);
            if($tema)
            {
               if($tema->imagen AND $tema->activo)
               {
                  $noti->preview = $tema->imagen;
                  $noti->save();
                  break;
               }
            }
         }
         
         /// ahora buscamos una previsualización
         $preview = new inme_noticia_preview();
         $preview->load($noti->url, $noti->texto);
         if($preview->type)
         {
            /**
             * nos interesan previews de youtube y vimeo, así como imágenes de imgur,
             * PERO si es una imagen normal, solamente la queremos si no tenemos nada.
             */
            if( is_null($noti->preview) AND ($preview->type == 'imgur' OR $preview->type == 'image') )
            {
               $noti->preview = $preview->preview();
               $noti->texto .= "\n<div class='thumbnail'>\n<img src='".$preview->link."' alt='".$noti->titulo."'/>\n</div>";
               $noti->editada = TRUE;
               $noti->save();
            }
            else if($preview->type == 'youtube')
            {
               $noti->preview = $preview->preview();
               $noti->texto = '<div class="embed-responsive embed-responsive-16by9">'
                       .'<iframe class="embed-responsive-item" src="//www.youtube-nocookie.com/embed/'.$preview->filename.'"></iframe>'
                       .'</div><br/>'.$noti->texto;
               $noti->editada = TRUE;
               $noti->save();
            }
            else if($preview->type == 'vimeo')
            {
               $noti->preview = $preview->preview();
               $noti->texto = '<div class="embed-responsive embed-responsive-16by9">'
                       .'<iframe class="embed-responsive-item" src="//player.vimeo.com/video/'.$preview->filename.'"></iframe>'
                       .'</div><br/>'.$noti->texto;
               $noti->editada = TRUE;
               $noti->save();
            }
         }
         else if( is_null($noti->preview) )
         {
            /// exploramos la página para buscar imágenes
            $html = $preview->curl_download($noti->url);
            
            $txt_adicional = FALSE;
            
            $urls = array();
            if( preg_match_all('@<meta property="og:image" content="([^"]+)@', $html, $urls) )
            {
               foreach($urls[1] as $url)
               {
                  $preview->load($url);
                  if($preview->type AND stripos($url, 'logo') === FALSE AND $noti->preview != $preview->link)
                  {
                     $noti->preview = $preview->preview();
                     $noti->save();
                     
                     $txt_adicional = "\n<div class='thumbnail'>\n<img src='".$preview->link."' alt='".$noti->titulo."'/>\n</div>";
                     break;
                  }
               }
            }
            
            if(!$preview->type)
            {
               /// buscamos vídeos de youtube o vimeo
               $urls = array();
               if( preg_match_all('@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@', $html, $urls) )
               {
                  foreach($urls[0] as $url)
                  {
                     foreach( array('youtube', 'youtu.be', 'vimeo') as $domain )
                     {
                        if( strpos($url, $domain) !== FALSE )
                        {
                           $preview->load($url);
                           if( in_array($preview->type, array('youtube', 'vimeo')) )
                           {
                              $noti->preview = $preview->preview();
                              $noti->save();
                              
                              if($preview->type == 'youtube')
                              {
                                 $txt_adicional = '<div class="embed-responsive embed-responsive-16by9">'
                                            .'<iframe class="embed-responsive-item" src="//www.youtube-nocookie.com/embed/'.$preview->filename.'"></iframe>'
                                            .'</div>';
                              }
                              else if($preview->type == 'vimeo')
                              {
                                 $txt_adicional = '<div class="embed-responsive embed-responsive-16by9">'
                                            .'<iframe class="embed-responsive-item" src="//player.vimeo.com/video/'.$preview->filename.'"></iframe>'
                                            .'</div>';
                              }
                              break;
                           }
                        }
                     }
                     
                     if($preview->type)
                     {
                        break;
                     }
                  }
               }
            }
            
            if($txt_adicional)
            {
               $noti->texto .= $txt_adicional;
               $noti->save();
            }
         }
      }
   }
   
   private function comprobar_temas()
   {
      $noti0 = new inme_noticia_fuente();
      $tema0 = new inme_tema();
      
      /**
       * Realizamos una busqueda en las noticias para asignarle tema
       */
      $sql = "SELECT * FROM inme_temas WHERE busqueda != '' ORDER BY popularidad DESC";
      $data = $this->db->select_limit($sql, 20, 0);
      if($data)
      {
         foreach($data as $d)
         {
            $tema = new inme_tema($d);
            
            foreach($noti0->search($tema->busqueda) as $no)
            {
               $no->set_keyword($tema->codtema);
               $no->save();
            }
         }
      }
      
      $total = $tema0->count();
      while($total > 0)
      {
         $tema0->cron_job();
         $total -= FS_ITEM_LIMIT;
         echo 'T';
      }
   }
}

new inme_cron($db);