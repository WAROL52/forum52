"use client";

import { type ComponentProps, useId } from "react";
import {
  Field,
  FieldDescription,
  FieldError,
  FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { createFieldComponent } from "@/forms/create-field-component";
import { AppField } from "@/forms/fields/app-field";

export type InputFieldProps = ComponentProps<"input">;

export const InputField = createFieldComponent<InputFieldProps>(
  ({ inputProps, ...fieldProps }) => {
    const id = useId();
    return (
      <AppField {...fieldProps}>
        {({ field, fieldState }) => (
          <Field data-invalid={fieldState.invalid}>
            {fieldProps.label && (
              <FieldLabel htmlFor={id}>{fieldProps.label}</FieldLabel>
            )}
            <Input
              {...inputProps}
              {...field}
              value={field.value}
              defaultValue={fieldProps.defaultValue}
              id={id}
              aria-invalid={fieldState.invalid}
              placeholder={fieldProps.placeholder}
            />
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
