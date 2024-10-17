<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * @Route("/reinitialisation-mdp")
 */
class ResetPasswordController extends Controller
{
    use ResetPasswordControllerTrait;

    private $resetPasswordHelper;
    private $entityManager;
    private $logger;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Display & process form to request a password reset.
     *
     * @Route("", name="app_forgot_password_request")
     */
    public function request(Request $request, MailerInterface $mailer): Response
    {
        $this->logger->debug('Password reset request received.');
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);
        $setting = $this->entityManager->getRepository('App\Entity\Setting')->getFromCache($_SERVER['APP_ENV']);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->debug("password reset form validated, resetting password");
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $mailer
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
            'setting' => $setting
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     *
     * @Route("/verifier-courriel", name="app_check_email")
     */
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     *
     * @Route("/reinitialisation/{token}", name="app_reset_password")
     */
    public function reset(Request $request, UserPasswordEncoderInterface $userPasswordEncoder, string $token = null): Response
    {
        $this->logger->debug("Password reset token received : {$token}");
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                'There was a problem validating your reset request - %s',
                $e->getReason()
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode(hash) the plain password, and set it.
            $encodedPassword = $userPasswordEncoder->encodePassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $this->entityManager->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData/*, MailerInterface $mailer*/): RedirectResponse
    {
        $this->logger->debug("Check if email is linked to an user account : $emailFormData ");
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            $this->logger->debug("No user found for email : $emailFormData");
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $userName = $user->getUsername();
            $this->logger->debug("generating token for user $userName");
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
            $this->logger->debug("Token generated for user $userName");
        } catch (ResetPasswordExceptionInterface $e) {
            // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'app_forgot_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            // $this->addFlash('reset_password_error', sprintf(
            //     'There was a problem handling your password reset request - %s',
            //     $e->getReason()
            // ));
            $this->logger->warning("Token not generated for user $userName because : " . $e->getReason());
            return $this->redirectToRoute('app_check_email');
        }

        $msg = $this->renderView('reset_password/email.html.twig',
            array('resetToken' => $resetToken,
                'username' => $user->getUsername())
        );

        $email = (new \Swift_Message())
            ->setFrom(array('ne_pas_repondre@easyamap.fr' => 'easyamap'))
            ->setTo($user->getEmail())
            ->setSubject('EasyAMAP : Réinitialisation de votre mot de passe')
            ->setBody($msg)
            ->setContentType('text/html');

        $mailer = $this->get('mailer');

        $mailer->send($email);

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }
}
