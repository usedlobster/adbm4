<?php
    
    namespace sys\db;
    
    /**
     * @method static Get0( string $param  , string[] $array )
     * @method static GetAll0( string $sql , string[] $array )
     * @method static GetAllN( string $sql , string[] $array )
     * @method static error() : string
     * @method static Exec(string $string , array $array = [] )
     * @method static LockExec(string $string , array $array = [] , string $lock = '')
     */
    class SQL
    {
        protected static ?SQLPDO $instance = null ;
        
        public static function __callstatic( $name, $args = [] )
        {
            
            if ( static::$instance == null  )
                static::$instance = new SQLPDO(  );
            
            // if ( method_exists( static::$instance, $name ) )
            return call_user_func_array( [ static::$instance , $name ] , $args );
        
        }



    }