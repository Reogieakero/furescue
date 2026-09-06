<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="refresh" content="0;url=/auth/login.php" />
    <title>Signing out…</title>
  </head>
  <body>
    <script>
      ["furescue_access_token", "furescue_refresh_token", "furescue_user"].forEach((k) =>
        localStorage.removeItem(k)
      );
      location.replace("/auth/login.php");
    </script>
  </body>
</html>
