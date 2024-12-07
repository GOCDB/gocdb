<?php
  require_once __DIR__.'/../../lib/Gocdb_Services/Factory.php';
  $configServ = \Factory::getConfigService();
  $configServ->setLocalInfoOverride($_SERVER['SERVER_NAME']);
  $origin = 'https://' . $_SERVER['HTTP_HOST'];
  $brandingFilePAth = __DIR__ . '/../web_portal/css/branding.css';
?>
<!doctype html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>GOCDB</title>
  <link rel="stylesheet" type="text/css" href="<?php echo $origin; ?>/portal/css/web_portal.php" />
  <?php

  if (file_exists($brandingFilePAth)) {
    echo '<link rel="stylesheet" type="text/css" href="'
      . $origin . '/portal/css/branding.css" />';
  }
  ?>
  <link rel="SHORTCUT ICON" href="Logo-1.4-FavIcon-32x32.regional.ico" />
  <link href="https://fonts.googleapis.com/css?family=PT+Sans" rel="stylesheet">
</head>

<body>
  <div class="page_container">
    <div class="centre_box">
      <div class="centrePageContainer">
        <div style="text-align: center;">
          <div style="width: 100%; margin-left: auto; margin-right: auto;">
            <img class="Landing_Logo" src="Logo-1.6.png" alt="The logo of the Grid Operations Centre Database service"/>
            <h1 class="Landing_Welcome">Welcome to GOCDB</h1>
          </div>
          <div style="width: 80%; margin-left: auto; margin-right: auto;">
            <?php
              echo '<p>Use of GOCDB is governed by the <a class="docLink hover" target="_blank" ';
              echo 'href="' . $configServ->getAUP() . '" title="' . $configServ->getAUPTitle() . '"';
              echo '>' . $configServ->getAUPTitle();
              echo '<img alt="new window logo"  class="new_window" src="/images/new_window.png">';
              echo '</a> which places restrictions on your use of the service.</p>';
              echo '<p>The <a class="docLink hover" target="_blank" ';
              echo 'href="' . $configServ->getPrivacyNotice() . '" title="' . $configServ->getPrivacyNoticeTitle() . '"';
              echo $configServ->getPrivacyNotice();
              echo '>' . $configServ->getPrivacyNoticeTitle();
              echo '<img alt="new window logo"  class="new_window" src="/images/new_window.png">';
              echo '</a> describes what personal data is collected and why, and your rights regarding this data.</p>';
              echo '<p> Please read these documents before accessing GOCDB.</p>';
            ?>
          </div>
          <div style="width: 80%; margin-left: auto; margin-right: auto;">
            <a href="/portal/" style="width:68%; font-size:1.7em" class="button gocdb_btn">Access GOCDB using your IGTF X.509 Certificate</a>
            <p>or</p>
            <p>Access GOCDB using one of the following:</p>
            <div>
              <?php
                $hostname = $_SERVER['HTTP_HOST'];
                $egi_target = urlencode("https://" . $hostname . "/portal/");
                $egi_redirect = "https://" . $hostname . "/Shibboleth.sso/Login?target=" . $egi_target;
              ?>
                <a style="width:30%; display:inline-block; font-size:1.5em" href="<?php echo $egi_redirect; ?>" class="button gocdb_btn">EGI Check-In</a>
            </div>
            <p>Browse the <a href="https://wiki.egi.eu/wiki/GOCDB" class="docLink hover">GOCDB documentation index</a> on the EGI wiki.</p>
          </div>
        </div>

        <div>
          <div class="Left_Logo_Row">
            <a href="https://stfc.ukri.org/" class="Sponsor_Link" target="_blank">
            <!-- /* Allow for STFC council symbol extending above the upper bound of the UKRI symbol -->
              <img style="height: 32px; margin: 2px;" class="Sponsor_Logo"
                src="/images/logos/ukri_stfc_with_text.png"
                alt="The logo of the Science and Technology Facilities Council" />
            </a>
            <a href="https://europa.eu/european-union/index_en" class="Sponsor_Link" target="_blank">
              <img class="Sponsor_Logo"
                src="/images/flags/eu.png"
                alt="The logo of the European Union" />
            </a>
            <a href="https://www.egi.eu" class="Sponsor_Link" target="_blank">
              <img class="Sponsor_Logo"
                src="/images/logos/egi.png"
                alt="The logo of the E G I Foundation" />
            </a>
            <a href="https://eoscfuture.eu/" class="Sponsor_Link" target="_blank">
              <img class="Sponsor_Logo"
                src="/images/logos/eosc_future.png"
                alt="The logo of the EOSC Future Horizon 20 20 project"
            </a>
            <a href="https://www.iris.ac.uk/" class="Sponsor_Link" target="_blank">
              <img class="Sponsor_Logo"
                src="/images/logos/iris_ac_uk.png"
                alt="The logo of the IRIS Community" />
            </a>
          </div>
          <div class="Copyright_Text">
            <p>The GOCDB service is provided by <a class="docLink" href="https://stfc.ukri.org/">STFC</a>,
              part of <a class="docLink" href="https://www.ukri.org">UK Research and Innovation</a>,
              and is co-funded by
              <a href="https://egi.eu">EGI</a> via <a href="https://www.egi.eu/project/egi-ace/">EGI-ACE</a>,
              <a href="https://eoscfuture.eu/">EOSC-Future</a> and the
              <a href="https://www.iris.ac.uk/">IRIS</a> community.
            </p>
            <p>
              Licensed under the <a class="docLink" href="https://www.apache.org/licenses/LICENSE-2.0">Apache 2 License</a>.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
