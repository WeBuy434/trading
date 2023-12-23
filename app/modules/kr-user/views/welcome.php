<div class="kr-wlcm-overlay kr-ov-nblr" kusr="<?php echo $User->_getUserID(true); ?>">
  <section nwlcm="1" class="kr-wlcm-nv1">
    <section class="kr-wlcm-overlay-fv">
      <h2 class="animated fadeInUp"><?php echo $Lang->tr('Welcome'); ?>, <?php echo $User->_getName(); ?> !</h2>
      <p class="animated fadeInUp"><?php echo $Lang->tr("Let's configure together your Dashboard."); ?></p>
    </section>
    <section>
      <header>
        <img src="<?php echo APP_URL; ?>/app/modules/kr-user/statics/img/world.svg">
        <h2 class="animated fadeInUp"><?php echo $Lang->tr('Select your language'); ?></h2>
      </header>
      <ul class="kr-wlcm-overlay-es" kr-wlcm-f="language">
        <?php
        foreach ($Lang->getListLanguage('') as $languageAbre => $languageName) {
          $s = preg_split("/\s\(.[^(]*\)/", $languageName);
          ?>
          <li kr-wlcm-v="<?php echo $languageAbre; ?>">
            <div>
              <img src="<?php echo APP_URL; ?>/assets/img/icons/languages/<?php echo $languageAbre; ?>.svg" alt="Français">
              <span><?php echo $s[0]; ?></span>
            </div>
          </li>
          <?php
        }
        ?>
      </ul>
    </section>
    <section>
      <header>
        <img src="<?php echo APP_URL; ?>/app/modules/kr-user/statics/img/currency.svg">
        <h2 class="animated fadeInUp"><?php echo $Lang->tr('Select your currency'); ?></h2>
      </header>
      <ul class="kr-wlcm-overlay-es" kr-wlcm-f="currency">
        <?php
        foreach ($Dashboard->_getListCurrency(500) as $dataCurrency) {
          ?>
          <li kr-wlcm-v="<?php echo $dataCurrency['code_iso_currency']; ?>">
            <div>
              <div>
                <?php echo $dataCurrency['symbol_currency']; ?>
              </div>
              <span><?php echo $dataCurrency['name_currency']; ?> <i>(<?php echo $dataCurrency['code_iso_currency']; ?>)</i></span>
            </div>
          </li>
          <?php
        }
        ?>
      </ul>
    </section>
    <section>
      <header>
        <img src="<?php echo APP_URL; ?>/app/modules/kr-user/statics/img/crypto.svg">
        <h2 class="animated fadeInUp"><?php echo $Lang->tr('Select your favorites crypto'); ?></h2>
        <span class="kr-wlcm-overlay-crypt-left"><i><?php echo $Dashboard->_getNumGraph(false); ?></i> <?php echo $Lang->tr('left need to be selected'); ?></span>
      </header>
      <ul class="kr-wlcm-overlay-es kr-wlcm-overlay-favcr" kr-wlcm-f="crypto">
        <?php
        foreach ($CryptoApi->_getCoinsList(50, false, true, null, 0, true) as $symbolCoin => $dataCoin) {
          ?>
          <li kr-wlcm-v="<?php echo $symbolCoin; ?>">
            <div>
              <?php if(file_exists('assets/img/icons/crypto/'.$symbolCoin.'.svg')): ?>
                <img src="<?php echo APP_URL; ?>/assets/img/icons/crypto/<?php echo $symbolCoin; ?>.svg" alt="">
              <?php endif; ?>
              <span><?php echo $dataCoin['CoinName']; ?></span>
            </div>
          </li>
          <?php
        }
        ?>
      </ul>
    </section>
    <section>
      <header>
        <img src="<?php echo APP_URL; ?>/app/modules/kr-user/statics/img/success.svg">
        <h2 class="animated fadeInUp"><?php echo $Lang->tr('Configuration complete !'); ?></h2>
        <span><?php echo $App->_getAppTitle(); ?> <?php echo $Lang->tr('will now refresh'); ?></span>
      </header>
    </section>
  </section>
</div>
