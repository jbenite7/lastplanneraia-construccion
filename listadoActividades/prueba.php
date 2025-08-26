<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload de archivos con Ajax</title>
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
</head>
<body>
  <form enctype="multipart/form-data" id="formuploadajax" method="post">
    Nombre: <input type="text" name="nombre" placeholder="Escribe tu nombre">
    <br />
    <input  type="file" id="archivo1" name="archivo1"/>
    <br />
    <input type="submit" value="Subir archivos" onclick="registra_paciente();"/>
  </form>
  <div id="mensaje"></div>


  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script>
    function registra_paciente(){
      var parametros = new FormData($("#formuploadajax")[0]);
    }
  </script>
</body>
</html>
