"use client";

import { type ComponentProps, useId } from "react";
import {
  Field,
  FieldDescription,
  FieldError,
  FieldLabel,
} from "@/components/ui/field";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { createFieldComponent } from "@/forms/create-field-component";
import { AppField } from "@/forms/fields/app-field";

export type SelectFieldProps = ComponentProps<typeof Select> & {
  options: { label: string; value: string }[];
};

export const SelectField = createFieldComponent<SelectFieldProps>(
  ({ inputProps, ...fieldProps }) => {
    const id = useId();
    return (
      <AppField {...fieldProps}>
        {({ field, fieldState }) => (
          <Field data-invalid={fieldState.invalid}>
            {fieldProps.label && (
              <FieldLabel htmlFor={id}>{fieldProps.label}</FieldLabel>
            )}
            <Select
              {...inputProps}
              {...field}
              value={field.value || fieldProps.defaultValue || ""}
              onValueChange={field.onChange}
            >
              <SelectTrigger className="w-[180px]">
                <SelectValue
                  aria-invalid={fieldState.invalid}
                  placeholder={fieldProps.placeholder}
                  id={id}
                />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  {inputProps?.options?.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectGroup>
              </SelectContent>
            </Select>
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
