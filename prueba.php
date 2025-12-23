      <?php

      $codigoBodega = 'PB'; // Este valor lo defines tú o lo recibes por POST

      $ipDestino = obtenerIPBodega($codigoBodega, $conn_usersdb);

      if ($ipDestino) {
        $conn_camionero = conectarCamioneroPorIP($ipDestino);

        if ($conn_camionero) {
          echo "✅ Conectado a DATA TESORERIA de la bodega $codigoBodega con IP $ipDestino<br>";

        // Aquí puedes hacer queries dinámicos en $conn_camionero
        /*
        $query = $conn_camionero->query("SELECT * FROM tabla_de_ejemplo");
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            // Mostrar resultados
        }
        */
      } else {
        echo "❌ No se pudo conectar a la base de datos de la bodega $codigoBodega<br>";
      }
    } else {
      echo "❌ No se encontró IP para la bodega $codigoBodega<br>";
    }
    ?>