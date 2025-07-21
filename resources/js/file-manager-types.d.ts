/**
 * Type declarations for File Manager
 */

interface Window {
  fileManagerState: {
    initialized: boolean;
    initSource: string | null;
    instance: any;
  };
  fileManagerAlreadyInitialized: boolean;
  fileManagerRegistered: boolean;
  fileManagerInitialized: boolean;
  debugFileManagerState: () => void;
  initializeFileManager: (source: string, options?: any) => any;
  FileManagerLazyLoader: any;
  Alpine: any;
  employeeUploadConfig?: {
    associateMessageUrl: string;
    batchCompleteUrl: string;
  };
}