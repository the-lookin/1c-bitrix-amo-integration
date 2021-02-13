<?
  include_once __DIR__ . '/bootstrap.php';
  $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($_GET['code']);
  print_r($accessToken);
?>
