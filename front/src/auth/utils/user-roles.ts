import type { User } from "@/schema/auth.schema";
import { UserRole, type UserRoleType } from "@/schema/auth.schema";

/**
 * Check if a user has a specific role
 */
export function hasRole(user: User, role: UserRoleType): boolean {
  return user.roles.includes(role);
}

/**
 * Check if a user is a super admin
 */
export function isSuperAdmin(user: User): boolean {
  return hasRole(user, UserRole.SUPER_ADMIN);
}

/**
 * Check if a user is an admin
 */
export function isAdmin(user: User): boolean {
  return hasRole(user, UserRole.ADMIN);
}

/**
 * Check if a user is a standard user
 */
export function isUser(user: User): boolean {
  return hasRole(user, UserRole.USER);
}

/**
 * Check if a user has at least one of the specified roles
 */
export function hasAnyRole(user: User, roles: UserRoleType[]): boolean {
  return roles.some((role) => hasRole(user, role));
}

/**
 * Check if a user has all of the specified roles
 */
export function hasAllRoles(user: User, roles: UserRoleType[]): boolean {
  return roles.every((role) => hasRole(user, role));
}

/**
 * Check if a user is an admin or super admin
 */
export function isAdminOrSuperAdmin(user: User): boolean {
  return hasAnyRole(user, [UserRole.ADMIN, UserRole.SUPER_ADMIN]);
}
