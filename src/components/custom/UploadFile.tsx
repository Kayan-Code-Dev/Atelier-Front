import * as React from "react";
import { Upload, X, File as FileIcon, Image as ImageIcon } from "lucide-react";
import { useFormContext, Controller, type FieldValues } from "react-hook-form";

import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";

export interface UploadFileProps {
  /** The selected file(s) value - File for single, File[] for multiple */
  value?: File | File[];
  /** Callback when file(s) change */
  onChange?: (file: File | File[] | undefined) => void;
  /** Label text displayed above the upload area */
  label?: string;
  /** Whether to show the label */
  showLabel?: boolean;
  /** Whether the component is disabled */
  disabled?: boolean;
  /** Custom className for the container */
  className?: string;
  /** Custom className for the upload area */
  uploadAreaClassName?: string;
  /** Whether to allow multiple file selection */
  multiple?: boolean;
  /** Accepted file types (e.g., "image/*", ".pdf,.doc") */
  accept?: string;
  /** Maximum file size in bytes */
  maxSize?: number;
  /** Maximum number of files (only applies when multiple is true) */
  maxFiles?: number;
  /** Placeholder text when no files are selected */
  placeholder?: string;
  /** ID for the input element */
  id?: string;
  /** Whether to show file previews */
  showPreview?: boolean;
  /** Custom error message */
  error?: string;
  /** Name for react-hook-form integration (if used within FormField) */
  name?: string;
}

export function UploadFile({
  value,
  onChange,
  label,
  showLabel = true,
  disabled = false,
  className,
  uploadAreaClassName,
  multiple = false,
  accept,
  maxSize,
  maxFiles,
  placeholder = multiple ? "Select files" : "Select a file",
  id,
  showPreview = true,
  error,
  name,
}: UploadFileProps) {
  const [isDragging, setIsDragging] = React.useState(false);
  const [internalError, setInternalError] = React.useState<string | undefined>(
    error
  );
  const [imagePreviews, setImagePreviews] = React.useState<
    Map<string, string>
  >(new Map());
  const fileInputRef = React.useRef<HTMLInputElement>(null);
  const previewsRef = React.useRef<Map<string, string>>(new Map());

  // Sync error state
  React.useEffect(() => {
    setInternalError(error);
  }, [error]);

  // Check if file is an image
  const isImageFile = (file: File): boolean => {
    return file.type.startsWith("image/");
  };

  // Generate preview URLs for image files
  React.useEffect(() => {
    const previews = new Map<string, string>();
    const currentPreviewKeys = new Set<string>();

    filesArray.forEach((file) => {
      if (isImageFile(file)) {
        // Use file name + size + lastModified as unique key
        const fileKey = `${file.name}-${file.size}-${file.lastModified}`;
        currentPreviewKeys.add(fileKey);

        // Only create new preview if it doesn't exist
        const existingPreview = previewsRef.current.get(fileKey);
        if (existingPreview) {
          // Keep existing preview
          previews.set(fileKey, existingPreview);
        } else {
          // Create new preview
          const previewUrl = URL.createObjectURL(file);
          previews.set(fileKey, previewUrl);
        }
      }
    });

    // Revoke URLs for files that are no longer in the array
    previewsRef.current.forEach((url, key) => {
      if (!currentPreviewKeys.has(key)) {
        URL.revokeObjectURL(url);
      }
    });

    // Update ref and state
    previewsRef.current = previews;
    setImagePreviews(previews);

    // Cleanup on unmount
    return () => {
      previewsRef.current.forEach((url) => {
        URL.revokeObjectURL(url);
      });
      previewsRef.current.clear();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [value]);

  // Get files array from value
  const filesArray = React.useMemo(() => {
    if (!value) return [];
    return Array.isArray(value) ? value : [value];
  }, [value]);

  const validateFile = (file: File): string | null => {
    if (maxSize && file.size > maxSize) {
      return `File "${file.name}" exceeds maximum size of ${formatFileSize(maxSize)}`;
    }
    return null;
  };

  const handleFiles = (newFiles: FileList | null) => {
    if (!newFiles || newFiles.length === 0) return;

    setInternalError(undefined);

    const fileArray = Array.from(newFiles);
    const validationErrors: string[] = [];

    // Validate each file
    fileArray.forEach((file) => {
      const error = validateFile(file);
      if (error) validationErrors.push(error);
    });

    if (validationErrors.length > 0) {
      setInternalError(validationErrors.join(", "));
      return;
    }

    if (multiple) {
      const currentFiles = Array.isArray(value) ? value : value ? [value] : [];
      const totalFiles = currentFiles.length + fileArray.length;

      if (maxFiles && totalFiles > maxFiles) {
        setInternalError(
          `Maximum ${maxFiles} file${maxFiles > 1 ? "s" : ""} allowed. You are trying to add ${fileArray.length} file${fileArray.length > 1 ? "s" : ""} but only ${maxFiles - currentFiles.length} slot${maxFiles - currentFiles.length > 1 ? "s" : ""} remaining.`
        );
        return;
      }

      const updatedFiles = [...currentFiles, ...fileArray];
      onChange?.(updatedFiles);
    } else {
      onChange?.(fileArray[0]);
    }
  };

  const handleFileInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    handleFiles(e.target.files);
    // Reset input to allow selecting the same file again
    if (fileInputRef.current) {
      fileInputRef.current.value = "";
    }
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (!disabled) {
      setIsDragging(true);
    }
  };

  const handleDragLeave = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);

    if (disabled) return;

    handleFiles(e.dataTransfer.files);
  };

  const removeFile = (index: number) => {
    if (disabled) return;

    const file = filesArray[index];
    if (file && isImageFile(file)) {
      // Revoke preview URL if it exists
      const fileKey = `${file.name}-${file.size}-${file.lastModified}`;
      const previewUrl = imagePreviews.get(fileKey);
      if (previewUrl) {
        URL.revokeObjectURL(previewUrl);
        setImagePreviews((prev) => {
          const newMap = new Map(prev);
          newMap.delete(fileKey);
          return newMap;
        });
      }
    }

    if (multiple) {
      const currentFiles = Array.isArray(value) ? value : [];
      const updatedFiles = currentFiles.filter((_, i) => i !== index);
      onChange?.(updatedFiles.length > 0 ? updatedFiles : undefined);
    } else {
      onChange?.(undefined);
    }
  };

  const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + " " + sizes[i];
  };

  const handleButtonClick = () => {
    if (!disabled && fileInputRef.current) {
      fileInputRef.current.click();
    }
  };

  const displayValue = multiple
    ? filesArray.length > 0
      ? `${filesArray.length} file${filesArray.length > 1 ? "s" : ""} selected`
      : placeholder
    : value
    ? (value as File).name
    : placeholder;

  return (
    <div className={cn("flex flex-col gap-3", className)}>
      {showLabel && label && (
        <Label htmlFor={id || "upload-file"} className="px-1">
          {label}
        </Label>
      )}
      <div
        className={cn(
          "relative flex flex-col items-center justify-center rounded-md border-2 border-dashed transition-colors",
          isDragging && !disabled
            ? "border-primary bg-primary/5"
            : "border-input bg-background hover:border-primary/50",
          disabled && "opacity-50 cursor-not-allowed",
          uploadAreaClassName
        )}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
      >
        <input
          ref={fileInputRef}
          type="file"
          id={id || "upload-file"}
          name={name}
          multiple={multiple}
          accept={accept}
          disabled={disabled}
          onChange={handleFileInputChange}
          className="hidden"
        />

        <div className="flex flex-col items-center justify-center gap-4 p-6 w-full">
          <div className="flex flex-col items-center gap-2">
            <Upload
              className={cn(
                "h-8 w-8",
                filesArray.length > 0 ? "text-primary" : "text-muted-foreground"
              )}
            />
            <div className="text-center">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={handleButtonClick}
                disabled={disabled}
                className="mb-2"
              >
                {multiple ? "Choose Files" : "Choose File"}
              </Button>
              <p className="text-sm text-muted-foreground mt-2">
                {multiple
                  ? "or drag and drop files here"
                  : "or drag and drop a file here"}
              </p>
              {accept && (
                <p className="text-xs text-muted-foreground mt-1">
                  Accepted: {accept}
                </p>
              )}
              {maxSize && (
                <p className="text-xs text-muted-foreground mt-1">
                  Max size: {formatFileSize(maxSize)}
                </p>
              )}
            </div>
          </div>

          {filesArray.length > 0 && (
            <div className="w-full mt-4">
              <p className="text-sm font-medium mb-2 text-center">
                {displayValue}
              </p>
              {showPreview && (
                <div className="flex flex-col gap-2 max-h-48 overflow-y-auto">
                  {filesArray.map((file, index) => {
                    const fileKey = `${file.name}-${file.size}-${file.lastModified}`;
                    const previewUrl = imagePreviews.get(fileKey);
                    const isImage = isImageFile(file);

                    return (
                      <div
                        key={`${file.name}-${index}`}
                        className="flex items-center gap-2 p-2 rounded-md bg-muted/50 hover:bg-muted transition-colors"
                      >
                        {isImage && previewUrl ? (
                          <div className="relative h-12 w-12 shrink-0 rounded-md overflow-hidden border border-border">
                            <img
                              src={previewUrl}
                              alt={file.name}
                              className="h-full w-full object-cover"
                            />
                          </div>
                        ) : (
                          <div className="h-12 w-12 shrink-0 rounded-md border border-border flex items-center justify-center bg-muted">
                            {isImage ? (
                              <ImageIcon className="h-5 w-5 text-muted-foreground" />
                            ) : (
                              <FileIcon className="h-5 w-5 text-muted-foreground" />
                            )}
                          </div>
                        )}
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-medium truncate">
                            {file.name}
                          </p>
                          <p className="text-xs text-muted-foreground">
                            {formatFileSize(file.size)}
                          </p>
                        </div>
                        {!disabled && (
                          <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="h-6 w-6 shrink-0"
                            onClick={() => removeFile(index)}
                          >
                            <X className="h-4 w-4" />
                          </Button>
                        )}
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {internalError && (
        <p className="text-sm text-destructive px-1">{internalError}</p>
      )}
    </div>
  );
}

// Wrapper component for react-hook-form integration
export interface UploadFileFieldProps
  extends Omit<UploadFileProps, "value" | "onChange" | "name" | "error"> {
  /** Field name for react-hook-form */
  name: string;
  /** Whether to show error message from form */
  showError?: boolean;
}

export function UploadFileField({
  name,
  showError = true,
  ...props
}: UploadFileFieldProps) {
  const form = useFormContext<FieldValues>();

  if (!form) {
    throw new Error("UploadFileField must be used within a FormProvider");
  }

  return (
    <Controller
      name={name as any}
      control={form.control}
      render={({ field, fieldState }) => (
        <UploadFile
          {...props}
          name={name}
          value={field.value}
          onChange={field.onChange}
          error={
            showError && fieldState.error
              ? (fieldState.error.message as string) || "Invalid file"
              : undefined
          }
        />
      )}
    />
  );
}

