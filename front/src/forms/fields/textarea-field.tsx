"use client";

import { type ComponentProps, useId } from "react";
import {
  Field,
  FieldDescription,
  FieldError,
  FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { createFieldComponent } from "@/forms/create-field-component";
import { AppField } from "@/forms/fields/app-field";

export type TextareaFieldProps = ComponentProps<typeof Textarea>;

export const TextareaField = createFieldComponent<TextareaFieldProps>(
  ({ inputProps, ...fieldProps }) => {
    const id = useId();
    return (
      <AppField {...fieldProps}>
        {({ field, fieldState }) => (
          <Field data-invalid={fieldState.invalid}>
            {fieldProps.label && (
              <FieldLabel htmlFor={id}>{fieldProps.label}</FieldLabel>
            )}
            <Textarea
              {...inputProps}
              {...field}
              value={field.value || fieldProps.defaultValue || ""}
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
