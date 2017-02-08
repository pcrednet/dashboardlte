<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2016 Luismipr <luismipr@gmail.com>.
 * Copyright (C) 2016 Carlos García Gómez <neorazorx@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * Lpublished by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * LeGNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of dashboardlte
 *
 * @author Luismipr <luismipr@gmail.com>
 * @author Carlos García Gómez      <neorazorx@gmail.com>
 */

require_model('albaran_cliente.php');
require_model('factura_cliente.php');
require_model('factura_proveedor.php');
require_model('pedido_cliente.php');
require_model('presupuesto_cliente.php');
require_model('recibo_cliente.php');
require_model('recibo_proveedor.php');
require_model('servicio_cliente.php');


class dashboardlte extends fs_controller
{
   public $presupuestos;
   public $pedidos;
   public $albaranes;
   public $servicios;
   public $facturas;
   public $facturasprov;
   public $desde;
   public $hasta;
   public $mes;
   public $intervalo;
   public $imp_desde;
   public $imp_hasta;
   
   public $soportado;
   public $repercutido;
   public $irpf;
   public $diferencia;
   
   public $total_ventas;
   public $por_ventas;
   public $total_compras;
   public $por_compras;
   public $beneficio;
   public $por_beneficio;
   public $desde_anterior;
   public $hasta_anterior;
   
   public $recibos_clientes;
   public $recibos_proveedores;
   public $pendiente_clientes;
   public $pendiente_proveedores;
   public $num_clientes;
   public $num_proveedores;
   
   public $show_facturacion;
   public $show_presped;
   public $show_servicios;
   public $show_tesoreria;
   public $topclientes;
   public $topproveedores;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'DashboardLTE', 'ventas');
   }
   
   protected function private_core()
   {
      $this->show_facturacion = in_array('facturacion_base', $GLOBALS['plugins']);
      $this->show_presped = class_exists('pedido_cliente');
      $this->show_servicios = class_exists('servicio_cliente');
      $this->show_tesoreria = class_exists('recibo_cliente');
      
      //rangos de fechas para buscar
      $this->intervalo = 'mes';
      if( isset($_GET['intervalo']) )
      {
         $this->intervalo = $_GET['intervalo'];
      }
      else if( isset($_COOKIE['intervalo_dash']) )
      {
         $this->intervalo = $_COOKIE['intervalo_dash'];
      }
      
      $this->desde = date('01-m-Y');
      $this->hasta = date('t-m-Y');
      
      if($this->intervalo == 'trimestre')
      {
         $this->desde = date('01-01-Y');
         $this->hasta = date('31-03-Y');
         
         $mes_actual = date('m');
         if($mes_actual >= 4 && $mes_actual <= 6)
         {
            $this->desde = date('01-04-Y');
            $this->hasta = date('30-06-Y');
         }
         else if($mes_actual >= 7 && $mes_actual <= 9)
         {
            $this->desde = date('01-07-Y');
            $this->hasta = date('30-09-Y');
         }
         else if($mes_actual >= 10 && $mes_actual <= 12)
         {
            $this->desde = date('01-10-Y');
            $this->hasta = date('31-12-Y');
         }
      }
      else if($this->intervalo == 'ano')
      {
         $this->desde = date('01-01-Y');
         $this->hasta = date('31-12-Y');
      }
      
      setcookie('intervalo_dash', $this->intervalo, time() + FS_COOKIES_EXPIRE);
      
      /// calculamos el periodo de impuestos
      $this->imp_desde = date('01-01-Y');
      $this->imp_hasta = date('31-03-Y');
      
      $mes_actual = date('m');
      if($mes_actual >= 4 && $mes_actual <= 6)
      {
         $this->imp_desde = date('01-04-Y');
         $this->imp_hasta = date('30-06-Y');
      }
      else if($mes_actual >= 7 && $mes_actual <= 9)
      {
         $this->imp_desde = date('01-07-Y');
         $this->imp_hasta = date('30-09-Y');
      }
      else if($mes_actual >= 10 && $mes_actual <= 12)
      {
         $this->imp_desde = date('01-10-Y');
         $this->imp_hasta = date('31-12-Y');
      }
      
      if($this->show_facturacion)
      {
         $this->mostrar_albaranes();
         $this->mostrar_facturas();
         $this->top_clientes();
         $this->top_proveedores();
         $this->mostrar_facturasprov();
         $this->totales();
         
         if($this->show_presped)
         {
            $this->mostrar_pedidos();
            $this->mostrar_presupuestos();
         }
         
         if($this->show_servicios)
         {
            $this->mostrar_servicios();
         }
         
         if($this->show_tesoreria)
         {
            $this->mostrar_recibos();
         }
      }
   }

   private function mostrar_facturas()
   {
      $fac0 = new factura_cliente();
      $this->facturas = $fac0->all_desde($this->desde, $this->hasta);
   }

   private function top_clientes()
   {
      $sql = "SELECT *,SUM(totaleuros) FROM facturascli"
              . " where fecha >= ".$this->empresa->var2str($this->desde)
              . " AND fecha <= ".$this->empresa->var2str($this->hasta).
              " GROUP BY codcliente  ORDER BY SUM(totaleuros) DESC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $f)
         {
            $this->topclientes[] = new \clientetop($f);
         }
      }
   }

   private function top_proveedores()
   {
      $sql = "SELECT *,SUM(totaleuros) FROM facturasprov"
              . " where fecha >= ".$this->empresa->var2str($this->desde)
              . " AND fecha <= ".$this->empresa->var2str($this->hasta).
              " GROUP BY codproveedor  ORDER BY SUM(totaleuros) DESC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $f)
         {
            $this->topproveedores[] = new \proveedorestop($f);
         }
      }
   }

   private function mostrar_facturasprov()
   {
      $fac0 = new factura_proveedor();
      $this->facturasprov = $fac0->all_desde($this->desde, $this->hasta);
   }

   public function labels_chart()
   {

      if($this->intervalo == 'mes')
      {
         $numdias = date('t');
         $diasmes = array();

         for ($i = 1; $i <= $numdias; $i++)
         {
            $diasmes[] = $i;
         }

         echo json_encode($diasmes);
      }
      else if ($this->intervalo == 'trimestre')
      {
         $mes_actual = date('m');
         if ($mes_actual >= 1 && $mes_actual <= 3)
         {
            $meses = array("Enero", "Febrero", "Marzo");
         }
         else if ($mes_actual >= 4 && $mes_actual <= 6)
         {
            $meses = array("Abril", "Mayo", "Junio");
         }
         else if ($mes_actual >= 7 && $mes_actual <= 9)
         {
            $meses = array("Julio", "Agosto", "Septiembre");
         }
         else if ($mes_actual >= 10 && $mes_actual <= 12)
         {
            $meses = array("Octubre", "Noviembre", "Diciembre");
         }

         echo json_encode($meses);
      }
      else if ($this->intervalo == 'ano')
      {
         $meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
         echo json_encode($meses);
      }
   }

   public function data_chart($tabla)
   {
      $json = array();
      
      $mes_actual = date('m');
      $ano_actual = date('Y');
      
      if($this->intervalo == 'mes')
      {
         $numdias = date('t');
         $sql = "SELECT sum( total ) as total, fecha from $tabla "
                 . " WHERE fecha > " . $this->empresa->var2str($this->desde)
                 . " AND fecha < " . $this->empresa->var2str($this->hasta) . " GROUP BY fecha ORDER BY fecha;";
         $data = $this->db->select($sql);

         $fecha = date('Y-m-1');
         for ($i = 1; $i <= $numdias; $i++)
         {
            if ($data)
            {
               $clave = array_search($fecha, array_column($data, 'fecha'));
               if ($clave !== FALSE)
               {
                  $json[] = $data[$clave]['total'];
               }
               else
               {
                  $json[] = 0;
               }
            }
            else
            {
               $json[] = 0;
            }

            $fecha = date('Y-m-d', strtotime($fecha . ' +1 day'));
         }
      }
      else if ($this->intervalo == 'trimestre')
      {
         $desde = $this->desde;
         $hasta = date('d-m-Y', strtotime($desde.' +1 month'));
         for($i = 1; $i <= 3; $i++)
         {
            $sql = "SELECT sum(total) as total from $tabla "
                 . " where fecha >= ".$this->empresa->var2str($desde)
                 . " and fecha < ".$this->empresa->var2str($hasta).";";
            $data = $this->db->select($sql);
            if($data)
            {
               $json[] = floatval($data['0']['total']);
            }
            
            $desde = date('d-m-Y', strtotime($desde.' +1 month'));
            $hasta = date('d-m-Y', strtotime($hasta.' +1 month'));
         }
      }
      else if($this->intervalo == 'ano')
      {
         for($i = 1; $i <= 12; $i++)
         {
            $numdias = date('t');
            $sql = "SELECT sum(total) as total from $tabla "
                    . " where fecha >= ".$this->empresa->var2str('1-'.$i.'-'.$ano_actual)
                    . " AND fecha <= ".$this->empresa->var2str($numdias.'-'.$i.'-'.$ano_actual).";";
            $data = $this->db->select($sql);
            if($data)
            {
               $json[] = floatval($data['0']['total']);
            }
         }
      }
      
      echo json_encode($json);
   }
   
   private function totales()
   {
      /// ventas
      $sql = "SELECT sum(totaliva+totalrecargo) as totaliva, sum(totalirpf) as totalirpf, sum(total) as total from facturascli"
              . " where fecha >= ".$this->empresa->var2str($this->desde)
              . " AND fecha <= ".$this->empresa->var2str($this->hasta).";";
      $data = $this->db->select($sql);
      if($data)
      {
         $this->repercutido = floatval($data['0']['totaliva']);
         $this->irpf = floatval($data['0']['totalirpf']);
         $this->total_ventas = floatval($data['0']['total']);
      }
      
      /// compras
      $sql = "SELECT sum(totaliva+totalrecargo) as totaliva, sum(totalirpf) as totalirpf, sum(total) as total from facturasprov"
              . " where fecha >= ".$this->empresa->var2str($this->desde)
              . " AND fecha <= ".$this->empresa->var2str($this->hasta).";";
      $data1 = $this->db->select($sql);
      if($data1)
      {
         $this->soportado = floatval($data1['0']['totaliva']);
         $this->irpf -= floatval($data1['0']['totalirpf']);
         $this->total_compras = floatval($data1['0']['total']);
      }
      
      $this->beneficio = $this->total_ventas - $this->total_compras;
      
      /// impuestos de ventas
      $sql = "SELECT sum(totaliva+totalrecargo) as totaliva, sum(totalirpf) as totalirpf"
              . " from facturascli where fecha >= ".$this->empresa->var2str($this->imp_desde)
              . " AND fecha <= ".$this->empresa->var2str($this->imp_hasta).";";
      $data = $this->db->select($sql);
      if($data)
      {
         $this->repercutido = floatval($data['0']['totaliva']);
         $this->irpf = floatval($data['0']['totalirpf']);
      }
      
      /// impuestos de compras
      $sql = "SELECT sum(totaliva+totalrecargo) as totaliva, sum(totalirpf) as totalirpf from facturasprov"
              . " where fecha >= ".$this->empresa->var2str($this->imp_desde)
              . " AND fecha <= ".$this->empresa->var2str($this->imp_hasta).";";
      $data1 = $this->db->select($sql);
      if($data1)
      {
         $this->soportado = floatval($data1['0']['totaliva']);
         $this->irpf -= floatval($data1['0']['totalirpf']);
      }
      
      $this->diferencia = $this->repercutido - $this->irpf - $this->soportado;
      
      /// periodo anterior
      $this->desde_anterior = date("1-m-Y", strtotime("-1 months"));
      $this->hasta_anterior = date("d-m-Y", strtotime("-1 months"));
      
      if($this->intervalo == 'trimestre' OR $this->intervalo == 'ano')
      {
         $this->desde_anterior = date("1-1-Y", strtotime($this->desde." -1 year"));
         $this->hasta_anterior = date("d-m-Y", strtotime($this->hasta." -1 year"));
      }
      
      /// ventas
      $sql = "SELECT sum(total) as total from facturascli"
              . " where fecha >= ".$this->empresa->var2str($this->desde_anterior)
              . " AND fecha <= ".$this->empresa->var2str($this->hasta_anterior).";";
      $data = $this->db->select($sql);
      if($data)
      {
         $this->por_ventas = 0;
         $total_ventas_anterior = floatval($data['0']['total']);
         if($total_ventas_anterior > 0)
         {
            $this->por_ventas = round($this->total_ventas * 100 / $total_ventas_anterior, 2);
         }
      }
      
      /// compras
      $sql = "SELECT sum(total) as total from facturasprov"
              . " where fecha >= ".$this->empresa->var2str($this->desde_anterior)
              . " AND fecha <= ".$this->empresa->var2str($this->hasta_anterior).";";
      $data2 = $this->db->select($sql);
      if($data2)
      {
         $this->por_compras = 0;
         $total_compras_anterior = floatval($data2['0']['total']);
         if($total_compras_anterior > 0)
         {
            $this->por_compras = round($this->total_compras * 100 / $total_compras_anterior, 2);
         }
      }
      
      /// beneficio
      $this->por_beneficio = 0;
      $beneficio_anterior = $total_ventas_anterior - $total_compras_anterior;
      if($beneficio_anterior > 0)
      {
         $this->por_beneficio = round($this->beneficio * 100 / $beneficio_anterior, 2);
      }
   }
   
   private function mostrar_pedidos()
   {
      if($this->show_presped)
      {
         $ped0 = new pedido_cliente;
         $this->pedidos = $ped0->all_desde($this->desde, $this->hasta);
      }
   }
   
   public function pedidos_pendientes()
   {
      if ($this->show_presped)
      {
         $data = $this->db->select("SELECT COUNT(idpedido) as total FROM pedidoscli WHERE idalbaran IS NULL AND status=0;");
         if ($data)
         {
            return intval($data[0]['total']);
         }
         else
            return 0;
      }
   }

   private function mostrar_presupuestos()
   {
      if ($this->show_presped)
      {
         $pres0 = new presupuesto_cliente();
         $this->presupuestos = $pres0->all_desde($this->desde, $this->hasta);
      }
   }

   public function presupuestos_pendientes()
   {
      if ($this->show_presped)
      {
         $data = $this->db->select("SELECT COUNT(idpresupuesto) as total FROM presupuestoscli WHERE idpedido IS NULL AND status=0;");
         if ($data)
         {
            return intval($data[0]['total']);
         }
         else
            return 0;
      }
   }

   private function mostrar_albaranes()
   {
      $alb0 = new albaran_cliente();
      $this->albaranes = $alb0->all_desde($this->desde, $this->hasta);
   }
   
   public function albaranes_pendientes()
   {
      $data = $this->db->select("SELECT COUNT(idalbaran) as total FROM albaranescli WHERE idfactura IS NULL;");
      if($data)
      {
         return intval($data[0]['total']);
      }
      else
         return 0;
   }
   
   private function mostrar_servicios()
   {
      if ($this->show_servicios)
      {
         $serv0 = new servicio_cliente();
         $this->servicios = $serv0->all_desde($this->desde, $this->hasta);
      }
      
   }
   
   public function servicios_pendientes()
   {
      $data = $this->db->select("SELECT COUNT(idservicio) as total FROM servicioscli WHERE idalbaran IS NULL;");
      if($data)
      {
         return intval($data[0]['total']);
      }
      else
         return 0;
   }
   
   private function mostrar_recibos()
   {
      if ($this->show_tesoreria)
      {
         $recli0 = new recibo_cliente();
         $recibos = $recli0->pendientes();
         $this->recibos_clientes = $recibos;

         foreach ($recibos as $r)
         {
            $this->pendiente_clientes += $r->importe;
         }

         $reciprov0 = new recibo_proveedor();
         $recibosprov = $reciprov0->pendientes();
         $this->recibos_proveedores = $recibosprov;

         foreach ($recibosprov as $r)
         {
            $this->pendiente_proveedores += $r->importe;
         }
      }
   }

}

class clientetop {
    public $codcliente;
    public $nombrecliente;
    public $totaleuros;
    public $url;

    public function __construct($f = FALSE)
    {
       $this->codcliente = $f['codcliente'];
       $this->nombrecliente = $f['nombrecliente'];
       $this->totaleuros = floatval($f['SUM(totaleuros)']);
       $this->url = 'index.php?page=ventas_cliente&cod='.$this->codcliente;
    }
}

class proveedorestop {
    public $codproveedor;
    public $nombreproveedor;
    public $totaleuros;
    public $url;

    public function __construct($f = FALSE)
    {
       $this->codproveedor = $f['codproveedor'];
       $this->nombreproveedor = $f['nombre'];
       $this->totaleuros = floatval($f['SUM(totaleuros)']);
       $this->url = 'index.php?page=compras_proveedor&cod='.$this->codproveedor;
    }
}