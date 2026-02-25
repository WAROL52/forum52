<?php

namespace App\Service;

use App\DTO\Auth\Request\CompletePasswordResetRequest;
use App\DTO\Auth\Request\CompleteRegistrationRequest;
use App\DTO\Shared\FilterCollection;
use App\DTO\User\Request\UpdateUserRequest;
use App\Entity\PendingVerification;
use App\Entity\User;
use App\Exception\ApiException;
use App\Helper\Paginate;
use App\Repository\PendingVerificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private Paginate $paginate,
        private VerificationCodeGenerator $codeGenerator,
        private MailService $mailService,
        private PendingVerificationRepository $pendingVerificationRepository,
    ) {
    }

    /**
     * Request verification code for an email
     */
    public function requestVerification(string $email): void
    {
        // Check if email already exists
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            throw new ApiException('EMAIL_ALREADY_EXISTS', Response::HTTP_BAD_REQUEST);
        }

        // Remove any existing verification request for this email
        $existingPending = $this->pendingVerificationRepository->findOneBy(['email' => $email]);
        if ($existingPending) {
            $this->entityManager->remove($existingPending);
            $this->entityManager->flush();
        }

        // Create a new verification request
        $verificationCode = $this->codeGenerator->generate();
        $pending = new PendingVerification();
        $pending->setEmail($email);
        $pending->setCode($verificationCode);
        $pending->setExpiresAt($this->codeGenerator->getExpirationTime());

        $this->entityManager->persist($pending);
        $this->entityManager->flush();

        // Send verification email
        $this->mailService->send(
            to: $email,
            subject: 'Vérification de votre email',
            htmlTemplate: 'emails/verification.html.twig',
            context: [
                'verificationCode' => $verificationCode,
                'expirationMinutes' => $this->codeGenerator->getExpirationMinutes(),
            ],
        );
    }

    /**
     * Request password reset
     */
    public function requestPasswordReset(string $email): void
    {
        // Check if user exists (silently to prevent email enumeration)
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            // Don't reveal whether email exists for security reasons
            return;
        }

        // Remove any existing reset request for this email
        $existingPending = $this->pendingVerificationRepository->findOneBy([
            'email' => $email,
            'type' => PendingVerification::TYPE_PASSWORD_RESET
        ]);
        if ($existingPending) {
            $this->entityManager->remove($existingPending);
            $this->entityManager->flush();
        }

        // Create a new reset request
        $resetCode = $this->codeGenerator->generate();
        $pending = new PendingVerification();
        $pending->setEmail($email);
        $pending->setCode($resetCode);
        $pending->setType(PendingVerification::TYPE_PASSWORD_RESET);
        $pending->setExpiresAt($this->codeGenerator->getExpirationTime());

        $this->entityManager->persist($pending);
        $this->entityManager->flush();

        // Send reset email
        $this->mailService->send(
            to: $email,
            subject: 'Réinitialisation de votre mot de passe',
            htmlTemplate: 'emails/password_reset.html.twig',
            context: [
                'verificationCode' => $resetCode,
                'expirationMinutes' => $this->codeGenerator->getExpirationMinutes(),
                'userName' => $user->getFirstName(),
            ],
        );
    }

    /**
     * Complete registration with verification code
     */
    public function completeRegistration(CompleteRegistrationRequest $request): User
    {
        // Check if email already exists
        $existingUser = $this->userRepository->findOneBy(['email' => $request->email]);
        if ($existingUser) {
            throw new ApiException('EMAIL_ALREADY_EXISTS', Response::HTTP_BAD_REQUEST, [
                ['field' => 'email', 'message' => 'EMAIL_ALREADY_EXISTS'],
            ]);
        }

        // Get verification request
        $pending = $this->pendingVerificationRepository->findOneBy([
            'email' => $request->email,
            'type' => PendingVerification::TYPE_REGISTRATION
        ]);
        if (!$pending) {
            throw new ApiException('INVALID_VERIFICATION_CODE', Response::HTTP_BAD_REQUEST, [
                ['field' => 'code', 'message' => 'VERIFICATION_CODE_INVALID'],
            ]);
        }

        // Check if code is expired
        if ($pending->isExpired()) {
            throw new ApiException('VERIFICATION_CODE_EXPIRED', Response::HTTP_BAD_REQUEST, [
                ['field' => 'code', 'message' => 'VERIFICATION_CODE_EXPIRED'],
            ]);
        }

        // Verify code
        if ($pending->getCode() !== $request->code) {
            throw new ApiException('INVALID_VERIFICATION_CODE', Response::HTTP_BAD_REQUEST, [
                ['field' => 'code', 'message' => 'VERIFICATION_CODE_INVALID'],
            ]);
        }

        // Create user
        $user = new User();
        $user->setEmail($request->email);
        $user->setFirstName($request->firstName);
        $user->setLastName($request->lastName);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $request->password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);

        // Remove verification request
        $this->entityManager->remove($pending);

        $this->entityManager->flush();

        // Send welcome email
        $this->mailService->send(
            to: $user->getEmail(),
            subject: 'Confirmation de votre compte',
            htmlTemplate: 'emails/welcome.html.twig',
            context: [
                'userName' => $user->getFirstName(),
            ],
        );

        return $user;
    }

    /**
     * Update a user
     */
    public function update(User $user, UpdateUserRequest $request): User
    {
        $user->setFirstName($request->firstName);
        $user->setLastName($request->lastName);

        $this->entityManager->flush();

        return $user;
    }

    /**
     * Delete a user
     */
    public function delete(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    /**
     * Get all users with pagination and filters
     */
    public function list(int $page = 1, int $limit = 10, FilterCollection $filters = null): array
    {
        $filters = $filters ?? new FilterCollection();
        $query = $this->userRepository->createFindAllQuery($filters);
        $total = $this->userRepository->countAll($filters);

        return $this->paginate->get($query, $page, $limit, $total);
    }

    /**
     * Check user credentials (for login)
     */
    public function checkCredentials(string $email, string $password): ?User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return null;
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }

        return $user;
    }

    /**
     * Complete password reset
     */
    public function completePasswordReset(CompletePasswordResetRequest $request): void
    {
        // Check if user exists
        $user = $this->userRepository->findOneBy(['email' => $request->email]);
        if (!$user) {
            throw new ApiException('INVALID_REQUEST', Response::HTTP_BAD_REQUEST);
        }

        // Get reset request
        $pending = $this->pendingVerificationRepository->findOneBy([
            'email' => $request->email,
            'type' => PendingVerification::TYPE_PASSWORD_RESET
        ]);
        if (!$pending) {
            throw new ApiException('INVALID_OR_EXPIRED_CODE', Response::HTTP_BAD_REQUEST);
        }

        // Check if code is expired
        if ($pending->isExpired()) {
            throw new ApiException('CODE_EXPIRED', Response::HTTP_BAD_REQUEST);
        }

        // Verify code
        if ($pending->getCode() !== $request->code) {
            throw new ApiException('INVALID_CODE', Response::HTTP_BAD_REQUEST);
        }

        // Update password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $request->newPassword);
        $user->setPassword($hashedPassword);

        // Remove reset request
        $this->entityManager->remove($pending);
        $this->entityManager->flush();
    }
}
