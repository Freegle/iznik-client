<?php
namespace PayPal\Handler;

use PayPal\Auth\PPCertificateCredential;
use PayPal\Auth\PPSubjectAuthorization;
use PayPal\Auth\PPTokenAuthorization;

class PPCertificateAuthHandler
  implements IPPHandler
{

    public function handle($httpConfig, $request, $options)
    {
        /** @var PPCertificateCredential $credential */
        $credential = $request->getCredential();

        if (!isset($credential)) {
            return;
        }

        $httpConfig->setSSLCert($credential->getCertificatePath(), $credential->getCertificatePassPhrase());
        $thirdPartyAuth = $credential->getThirdPartyAuthorization();

        switch ($request->getBindingType()) {
            case 'NV':
                if (!$thirdPartyAuth || !$thirdPartyAuth instanceof PPTokenAuthorization) {
                    $httpConfig->addHeader('X-PAYPAL-SECURITY-USERID', $credential->getUserName());
                    $httpConfig->addHeader('X-PAYPAL-SECURITY-PASSWORD', $credential->getPassword());
                    if ($thirdPartyAuth) {
                        $httpConfig->addHeader('X-PAYPAL-SECURITY-SUBJECT', $thirdPartyAuth->getSubject());
                    }
                }
                break;
            case 'SOAP':
                if ($thirdPartyAuth && $thirdPartyAuth instanceof PPTokenAuthorization) {
                    $request->addBindingInfo('securityHeader', '<ns:RequesterCredentials/>');
                } else {
                    $securityHeader = '<ns:RequesterCredentials><ebl:Credentials>';
                    $securityHeader .= '<ebl:Username>' . $credential->getUserName() . '</ebl:Username>';
                    $securityHeader .= '<ebl:Password>' . $credential->getPassword() . '</ebl:Password>';
                    if ($thirdPartyAuth && $thirdPartyAuth instanceof PPSubjectAuthorization) {
                        $securityHeader .= '<ebl:Subject>' . $thirdPartyAuth->getSubject() . '</ebl:Subject>';
                    }
                    $securityHeader .= '</ebl:Credentials></ns:RequesterCredentials>';
                    $request->addBindingInfo('securityHeader', $securityHeader);
                }
                break;
        }
    }

}
