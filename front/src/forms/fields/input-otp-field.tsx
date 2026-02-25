"use client";

import { type ComponentProps, useId } from "react";
import {
  Field,
  FieldDescription,
  FieldError,
  FieldLabel,
} from "@/components/ui/field";
import {
  InputOTP,
  InputOTPGroup,
  InputOTPSlot,
} from "@/components/ui/input-otp";
import { createFieldComponent } from "@/forms/create-field-component";
import { AppField } from "@/forms/fields/app-field";

export type InputOtpFieldProps = {
  length?: number;
};

export const InputOtpField = createFieldComponent<InputOtpFieldProps>(
  ({ inputProps, ...fieldProps }) => {
    const id = useId();
    const otpLength = inputProps.length || 6;
    return (
      <AppField {...fieldProps}>
        {({ field, fieldState }) => (
          <Field data-invalid={fieldState.invalid}>
            {fieldProps.label && (
              <FieldLabel htmlFor={id}>{fieldProps.label}</FieldLabel>
            )}
            <InputOTP
              {...inputProps}
              {...field}
              value={field.value || fieldProps.defaultValue || ""}
              id={id}
              aria-invalid={fieldState.invalid}
              placeholder={fieldProps.placeholder}
              maxLength={otpLength}
              minLength={otpLength}
            >
              <InputOTPGroup className="gap-2">
                {Array.from({ length: otpLength }).map((_, index) => (
                  <InputOTPSlot
                    className="rounded-md border size-12"
                    index={index}
                    key={index.toString()}
                  />
                ))}
              </InputOTPGroup>
            </InputOTP>
            {fieldProps.description && (
              <FieldDescription>{fieldProps.description}</FieldDescription>
            )}
            {fieldState.invalid && <FieldError errors={[fieldState.error]} />}
          </Field>
        )}
      </AppField>
    );
  },
);
