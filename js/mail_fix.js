/**
 * @file
 * Provides Javascript behaviors for restrict IP module.
 */
(function (Drupal, $) {
  /**
   * Behaviors to attach mailTo link.
   */
  Drupal.behaviors.restrictIpMailFix = {
    attach(context) {
      const mailDiv = $('#restrict_ip_contact_mail');
      const contactMail = mailDiv.text().replace('[at]', '@');
      mailDiv.html(`<a href="mailto:${contactMail}">${contactMail}</a>`);
    },
  };
})(Drupal, jQuery);
