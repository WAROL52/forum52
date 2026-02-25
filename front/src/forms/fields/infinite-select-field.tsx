"use client";

import { type ComponentProps, useId } from "react";
import {
	Field,
	FieldDescription,
	FieldError,
	FieldLabel,
} from "@/components/ui/field";
import { InfiniteSelect } from "@/components/ui/infinite-select";
import { createFieldComponent } from "@/forms/create-field-component";
import { AppField } from "@/forms/fields/app-field";

export type InfiniteSelectFieldProps = ComponentProps<typeof InfiniteSelect> & {
	data: unknown | undefined;
	isLoading: boolean;
	isFetchingNextPage: boolean;
	hasNextPage: boolean;
	fetchNextPage: () => void;
	error: Error | null;
	mapOption: (item: { label: string; value: string }) => {
		label: string;
		value: string;
	};
};

export const InfiniteSelectField =
	createFieldComponent<InfiniteSelectFieldProps>(
		({ inputProps, ...fieldProps }) => {
			const id = useId();
			return (
				<AppField {...fieldProps}>
					{({ field, fieldState }) => (
						<Field data-invalid={fieldState.invalid}>
							{fieldProps.label && (
								<FieldLabel htmlFor={id}>{fieldProps.label}</FieldLabel>
							)}
							<InfiniteSelect
								{...inputProps}
								{...field}
								value={field.value || fieldProps.defaultValue || ""}
								onValueChange={field.onChange}
							/>
							{fieldProps.description && (
								<FieldDescription>{fieldProps.description}</FieldDescription>
							)}
							{fieldState.invalid && <FieldError errors={[fieldState.error]} />}
						</Field>
					)}
				</AppField>
			);
		}
	);
