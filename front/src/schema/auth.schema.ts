import z from "zod";

/**
 * User roles as defined in the backend (api/src/Entity/User.php)
 */
export const UserRole = {
  SUPER_ADMIN: "ROLE_SUPER_ADMIN",
  ADMIN: "ROLE_ADMIN",
  USER: "ROLE_USER",
} as const;

export type UserRoleType = (typeof UserRole)[keyof typeof UserRole];

export const userRoleSchema = z.enum([
  UserRole.SUPER_ADMIN,
  UserRole.ADMIN,
  UserRole.USER,
]);
export const userSchema = z.object({
  id: z.string(),
  email: z.email(),
  name: z.string().min(2).max(100).optional(),
  image: z.url().optional(),
  createdAt: z.date(),
  updatedAt: z.date(),
  roles: z.array(userRoleSchema).nonempty(),
  isEmailValidated: z.boolean().default(false),
  businessId: z.string().nullish(),
});

export const sessionSchema = z
  .object({
    userId: z.string(),
    token: z.string(),
    expiresAt: z.date(),
    createdAt: z.date(),
    updatedAt: z.date(),
    freshAgeInSeconds: z.number().default(60 * 5),
    refresh_token: z.string().optional(),
    userAgent: z.string().optional(),
    ipAddress: z.string().optional(),
    disableSessionRefresh: z.boolean().default(false),
    businessId: z.string().nullish(),
  })
  .refine(
    (data) => {
      return data.createdAt <= new Date();
    },
    {
      message: "Session createdAt must be before current time",
    },
  )
  .refine(
    (data) => {
      return data.updatedAt >= data.createdAt;
    },
    {
      message: "Session updatedAt must be after or equal to createdAt",
    },
  )
  .refine(
    (data) => {
      return data.expiresAt > data.createdAt && data.expiresAt > data.updatedAt;
    },
    {
      message: "Session expiresAt must be after createdAt and updatedAt",
    },
  );

export const userSessionSchema = z
  .object({
    user: userSchema,
    session: sessionSchema,
  })
  .refine(
    (data) => {
      return data.session.userId === data.user.id;
    },
    {
      message: "Session userId does not match user id",
      path: ["session", "userId"],
    },
  );

export type User = z.infer<typeof userSchema>;
export type Session = z.infer<typeof sessionSchema>;
export type UserSession = z.infer<typeof userSessionSchema>;
